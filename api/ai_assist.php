<?php
/**
 * MEETORY - AI出品アシスト API エンドポイント
 * フロントエンドからの画像アップロードを受け付け、GeminiServiceで解析してJSONを返す
 */

header('Content-Type: application/json; charset=utf-8');

// 設定ファイルとGeminiServiceの読み込み
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/GeminiService.php';

// POST メソッドの確認
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

try {
    $tempFilePath = null;
    $mimeType = null;
    $hint = $_POST['hint'] ?? '';

    // 1. ファイルアップロード経由の場合
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];

        // サイズチェック
        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception('画像サイズが大きすぎます（最大10MBまで対応）');
        }

        // MIMEタイプチェック
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mimeType, $allowedMimes, true)) {
            throw new Exception('対応していない画像形式です（JPG, PNG, WebP形式に対応しています）');
        }

        // アップロードディレクトリの準備と保存
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $savedFileName = 'item_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . ($extension ?: 'jpg');
        $targetPath = UPLOAD_DIR . $savedFileName;

        $saved = is_uploaded_file($file['tmp_name'])
            ? move_uploaded_file($file['tmp_name'], $targetPath)
            : copy($file['tmp_name'], $targetPath);

        if (!$saved) {
            throw new Exception('画像の保存に失敗しました');
        }

        $tempFilePath = $targetPath;
        $imageUrl = 'uploads/' . $savedFileName;
    }
    // 2. Base64 画像送信の場合
    elseif (!empty($_POST['image_base64'])) {
        $base64Data = $_POST['image_base64'];
        if (preg_match('/^data:(image\/[a-zA-Z0-9\+\-]+);base64,(.+)$/', $base64Data, $matches)) {
            $mimeType = $matches[1];
            $decoded = base64_decode($matches[2]);
        } else {
            $decoded = base64_decode($base64Data);
            $mimeType = 'image/jpeg';
        }

        if (!$decoded) {
            throw new Exception('Base64画像のデコードに失敗しました');
        }

        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }

        $savedFileName = 'item_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.jpg';
        $targetPath = UPLOAD_DIR . $savedFileName;
        file_put_contents($targetPath, $decoded);

        $tempFilePath = $targetPath;
        $imageUrl = 'uploads/' . $savedFileName;
    } else {
        throw new Exception('商品画像が選択されていません');
    }

    // Gemini APIで解析実行
    $service = new GeminiService();
    $result = $service->analyzeProductImage($tempFilePath, $mimeType, $hint);

    // 画像URLを付与して返却
    $result['image_url'] = $imageUrl;

    echo json_encode([
        'success' => true,
        'data' => $result
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
