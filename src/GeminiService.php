<?php
/**
 * MEETORY - Gemini API 連携サービス
 * 企画書 (P.8, P.9, P.13) に基づくAI出品アシストの実装
 */

class GeminiService
{
    private string $apiKey;
    private string $apiUrl;

    public function __construct(string $apiKey = GEMINI_API_KEY, string $apiUrl = GEMINI_API_URL)
    {
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
    }

    /**
     * 商品画像を解析し、出品アシストデータ（JSON）を生成する
     *
     * @param string $imagePath アップロードされた画像ファイルパス
     * @param string $mimeType 画像のMIMEタイプ (例: image/jpeg, image/png)
     * @param string $hint ユーザーからの追加ヒント（任意）
     * @return array 解析結果
     * @throws Exception
     */
    public function analyzeProductImage(string $imagePath, string $mimeType, string $hint = ''): array
    {
        // APIキーが未設定の場合
        if (empty($this->apiKey)) {
            if (defined('ENABLE_DEMO_FALLBACK') && ENABLE_DEMO_FALLBACK) {
                return $this->getDemoAnalysis($hint);
            }
            throw new Exception('Gemini API キーが設定されていません。config.php または環境変数 GEMINI_API_KEY を設定してください。');
        }

        if (!file_exists($imagePath)) {
            throw new Exception('画像ファイルが見つかりません: ' . $imagePath);
        }

        // 画像を Base64 エンコード
        $imageData = base64_encode(file_get_contents($imagePath));

        // プロンプトの作成
        $prompt = $this->buildPrompt($hint);

        // Gemini API リクエストボディ
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $imageData
                            ]
                        ],
                        [
                            'text' => $prompt
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'response_mime_type' => 'application/json',
                'temperature' => 0.4
            ]
        ];

        // APIリクエスト実行
        $url = $this->apiUrl . '?key=' . urlencode($this->apiKey);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            if (defined('ENABLE_DEMO_FALLBACK') && ENABLE_DEMO_FALLBACK) {
                $demo = $this->getDemoAnalysis($hint);
                $demo['notice'] = 'Gemini API通信エラーのためデモデータを表示しています (' . $curlError . ')';
                return $demo;
            }
            throw new Exception('cURLエラー: ' . $curlError);
        }

        if ($httpCode !== 200) {
            $errData = json_decode($response, true);
            $errMsg = $errData['error']['message'] ?? ('HTTP Status ' . $httpCode);
            if (defined('ENABLE_DEMO_FALLBACK') && ENABLE_DEMO_FALLBACK) {
                $demo = $this->getDemoAnalysis($hint);
                $demo['notice'] = 'Gemini APIエラーのためデモデータを表示しています (' . $errMsg . ')';
                return $demo;
            }
            throw new Exception('Gemini API エラー: ' . $errMsg);
        }

        $resData = json_decode($response, true);
        $rawText = $resData['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if (empty($rawText)) {
            throw new Exception('Gemini API から応答を取得できませんでした。');
        }

        $parsed = json_decode($rawText, true);
        if (!$parsed || !is_array($parsed)) {
            throw new Exception('AIの出力をJSONとして解析できませんでした: ' . $rawText);
        }

        $parsed['is_demo'] = false;
        return $parsed;
    }

    /**
     * プロンプトを構築
     */
    private function buildPrompt(string $hint): string
    {
        $hintSection = "";
        if (!empty($hint)) {
            $hintSection = <<<HINT
【出品者から申告された商品の状態・補足メモ】
「{$hint}」

※重要指示:
- 出品者が入力した上記の状態・補足メモ（動作状況、使用期間、傷・汚れ、付属品など）を非常に重視してください。
- 写真からは判別しにくい内部動作や傷などの申告があれば、状態判定（condition/condition_detail）、相場推定（price_estimation）、商品説明文（description）に必ず的確に反映させてください。
HINT;
        }

        return <<<PROMPT
あなたはフリマECプラットフォーム「MEETORY（ミートリー）」の「AI出品アシスト」機能です。
提供された商品の写真と、出品者から入力された商品の状態メモを総合的に分析し、出品者が簡単かつ適正価格で出品できるよう、以下のJSON形式で結果を出力してください。

{$hintSection}

【出力フォーマット (JSON)】
{
  "item_name": "商品の正確な名称（メーカー名、製品名、カラー、型番などを含む分かりやすいタイトル）",
  "category": "カテゴリ（例: テレビゲーム / 周辺機器、家電・スマホ・カメラ、メンズファッション 等）",
  "condition": "状態の判定（次のいずれか1つ: '新品・未使用', '未使用に近い', '目立った傷や汚れなし', 'やや傷や汚れあり', '傷や汚れあり', '全体的に状態が悪い'）",
  "condition_detail": "画像と出品者申告から読み取れる状態の客観的な説明（傷、汚れ、使用感、動作状況、付属品の有無など）",
  "price_estimation": {
    "min": 相場の下限価格（整数、日本円、例: 3000）,
    "max": 相場の上限価格（整数、日本円、例: 5000）,
    "recommended": おすすめ出品価格（整数、日本円、相場中央〜売りやすい価格、例: 3980）,
    "reason": "相場とおすすめ価格の算出根拠（出品者の状態申告も加味した理由）"
  },
  "description": "フリマアプリに適した親切で魅力的な商品説明文（挨拶、商品の特徴、出品者申告の状態補足、付属品の有無、梱包・発送について）",
  "tags": ["関連する検索用タグ", "キーワード", "3〜6個程度"]
}

注意: 必ず有効なJSON文字列のみを出力してください。Markdownのコードブロック（```json ... ```）は不要です。
PROMPT;
    }

    /**
     * デモ用・モックデータの生成（スライドP.9の例示に準拠）
     */
    private function getDemoAnalysis(string $hint = ''): array
    {
        $hasDamage = !empty($hint) && (preg_match('/(傷|スレ|汚れ|割れ|難|不良)/u', $hint));
        $condition = $hasDamage ? 'やや傷や汚れあり' : '目立った傷や汚れなし';
        $conditionDetail = !empty($hint) 
            ? "出品者申告の状態: 「{$hint}」。写真と合わせて動作・外観状態を確認しました。"
            : '全体的に清潔感があり、ボタンやスティック周りに目立つ傷や汚れは見当たりません。';

        $recPrice = $hasDamage ? 3480 : 3980;
        $minPrice = $hasDamage ? 2800 : 3000;
        $maxPrice = $hasDamage ? 4500 : 5000;

        $hintNote = !empty($hint) ? "\n【状態・付属品について】\n{$hint}\n" : "\n【商品の状態】\n目立った傷や汚れなし（動作確認済み・清掃済み）\n";

        return [
            'is_demo' => true,
            'notice' => '現在デモモードで動作しています（Gemini API キーを設定するとリアルタイム画像解析に切り替わります）。',
            'item_name' => 'ワイヤレスゲームコントローラー ブラック (動作確認済)',
            'category' => 'テレビゲーム / プレイステーション / 周辺機器',
            'condition' => $condition,
            'condition_detail' => $conditionDetail,
            'price_estimation' => [
                'min' => $minPrice,
                'max' => $maxPrice,
                'recommended' => $recPrice,
                'reason' => '同型コントローラーの直近取引相場およびご記入いただいた状態（' . ($hint ?: '目立った傷なし') . '）を考慮した推奨価格です。'
            ],
            'description' => "ご覧いただきありがとうございます！\n\n【商品名】ワイヤレスゲームコントローラー\n【カラー】ブラック{$hintNote}\n大切に使用・保管しておりました。買い替えに伴い出品いたします。\n即購入歓迎です！丁寧にプチプチ等で梱包し、迅速に発送いたします。\nよろしくお願いいたします！",
            'tags' => ['ゲーム', 'コントローラー', '周辺機器', 'ワイヤレス', '動作確認済']
        ];
    }
}
