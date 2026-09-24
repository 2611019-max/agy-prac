<?php
require_once __DIR__ . '/config.php';
$hasApiKey = !empty(GEMINI_API_KEY);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AI出品アシスト | MEETORY ～出会いと物語～</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

  <!-- ヘッダー (企画書デザインに準拠) -->
  <header class="header">
    <div class="header-container">
      <a href="index.php" class="logo-group">
        <div class="logo-icon">💖</div>
        <div class="logo-text">
          <h1>meetory</h1>
          <p>～出会いと物語～</p>
        </div>
      </a>
      <div class="api-status-badge">
        <span class="status-dot <?php echo $hasApiKey ? '' : 'demo'; ?>"></span>
        <?php echo $hasApiKey ? 'Gemini 2.5 Flash 連携中' : 'デモモード稼働中'; ?>
      </div>
    </div>
  </header>

  <!-- メインコンテンツ -->
  <main class="main-container">
    <div class="page-header">
      <h2 class="page-title">AI出品アシスト</h2>
      <p class="page-subtitle">写真をアップロードするだけで、AIが商品の状態・相場・おすすめ価格を瞬時に提案します</p>

      <!-- ステップ進行バー (スライド P.9 準拠) -->
      <div class="steps-bar">
        <div class="step-item active">
          <div class="step-circle">1</div>
          <span>写真選択</span>
        </div>
        <div class="step-line"></div>
        <div class="step-item active">
          <div class="step-circle">2</div>
          <span>AI分析</span>
        </div>
        <div class="step-line"></div>
        <div class="step-item">
          <div class="step-circle">3</div>
          <span>出品確認</span>
        </div>
      </div>
    </div>

    <!-- 写真アップロードカード -->
    <div class="card" id="uploadCard">
      <div class="upload-dropzone" id="uploadDropzone">
        <div class="upload-icon-circle">📷</div>
        <p class="upload-main-text">商品写真をドラッグ＆ドロップ</p>
        <p class="upload-sub-text">またはここをクリックしてファイルを選択</p>
        <button type="button" class="btn-upload">写真から出品する</button>
        <input type="file" id="productImageInput" accept="image/jpeg,image/png,image/webp" style="display: none;">

        <!-- ワンクリック体験用サンプルボタン -->
        <div class="sample-tester">
          <span>画像がない場合はサンプルでテスト:</span>
          <button type="button" class="sample-btn" data-sample="ワイヤレスコントローラー">🎮 コントローラー</button>
          <button type="button" class="sample-btn" data-sample="ゲーミングヘッドセット">🎧 ヘッドセット</button>
          <button type="button" class="sample-btn" data-sample="ノートPC">💻 ノートPC</button>
        </div>
      </div>

      <!-- 写真プレビュー表示 -->
      <div class="preview-container" id="previewContainer">
        <div class="preview-image-wrapper">
          <img src="" alt="商品写真プレビュー" id="previewImg" class="preview-img">
          <button type="button" class="btn-reselect" id="btnReselect" title="写真を変更">×</button>
        </div>
      </div>
    </div>

    <!-- AI分析中オーバーレイ (スライド P.9 の演出) -->
    <div class="analyzing-overlay" id="analyzingOverlay">
      <div class="ai-badge">✨ AI出品アシスト</div>
      <h3 class="analyzing-title">AIが分析中...</h3>
      <ul class="analyzing-checklist">
        <li id="checkState"><span class="check-icon">✓</span> 商品の状態を分析</li>
        <li id="checkPrice"><span class="check-icon">✓</span> 相場価格を分析</li>
        <li id="checkSuggest"><span class="check-icon">✓</span> 出品情報を提案</li>
      </ul>
    </div>

    <!-- AI提案ハイライトカード (スライド P.9 準拠: おすすめ価格 ¥3,980) -->
    <div class="ai-result-hero" id="aiResultHero">
      <div class="ai-hero-header">
        <div class="ai-hero-title">
          <span>🤖 AI分析結果レポート</span>
          <span class="demo-pill" id="demoPill">デモモード</span>
        </div>
      </div>

      <div class="price-box-grid">
        <div class="price-item-hero">
          <div class="price-label">おすすめ価格</div>
          <div class="price-recommended-value" id="recommendedPriceDisplay">¥3,980</div>
        </div>
        <div class="price-item-hero">
          <div class="price-label">相場目安</div>
          <div class="price-range-value" id="priceRangeDisplay">¥3,000 〜 ¥5,000</div>
        </div>
      </div>

      <div class="condition-tag-row">
        <span class="badge-condition" id="conditionBadge">目立った傷や汚れなし</span>
        <span class="condition-detail-text" id="conditionDetailDisplay">写真から傷や汚れは見当たりません。</span>
      </div>

      <div class="ai-reason-text" id="aiReasonDisplay">
        同型商品の直近取引相場および外観状態を考慮した推奨価格です。
      </div>
    </div>

    <!-- 出品内容確認・編集フォーム (スライド P.9 準拠) -->
    <div class="card form-card" id="formCard">
      <div class="form-title">
        <span>出品内容の確認・編集</span>
        <small style="font-size: 12px; color: var(--text-muted); font-weight: normal;">※AIの提案を自由に編集できます</small>
      </div>

      <form id="listingForm">
        <!-- 商品名 -->
        <div class="form-group">
          <label class="form-label" for="inputTitle">
            商品名 <span class="required">*</span>
            <span class="auto-badge">AI自動入力</span>
          </label>
          <input type="text" id="inputTitle" class="form-input" placeholder="商品名を入力してください" required>
        </div>

        <!-- カテゴリ -->
        <div class="form-group">
          <label class="form-label" for="selectCategory">
            カテゴリ <span class="required">*</span>
            <span class="auto-badge">AI自動判定</span>
          </label>
          <select id="selectCategory" class="form-select" required>
            <option value="">カテゴリを選択</option>
            <option value="テレビゲーム / 周辺機器">テレビゲーム / 周辺機器</option>
            <option value="パソコン・PC周辺機器">パソコン・PC周辺機器</option>
            <option value="スマホ・タブレット・家電">スマホ・タブレット・家電</option>
            <option value="ファッション・衣服">ファッション・衣服</option>
            <option value="本・音楽・ゲーム">本・音楽・ゲーム</option>
            <option value="インテリア・生活雑貨">インテリア・生活雑貨</option>
            <option value="その他">その他</option>
          </select>
        </div>

        <!-- 商品の状態 -->
        <div class="form-group">
          <label class="form-label" for="selectCondition">
            商品の状態 <span class="required">*</span>
            <span class="auto-badge">AI自動判定</span>
          </label>
          <select id="selectCondition" class="form-select" required>
            <option value="新品・未使用">新品・未使用</option>
            <option value="未使用に近い">未使用に近い</option>
            <option value="目立った傷や汚れなし">目立った傷や汚れなし</option>
            <option value="やや傷や汚れあり">やや傷や汚れあり</option>
            <option value="傷や汚れあり">傷や汚れあり</option>
            <option value="全体的に状態が悪い">全体的に状態が悪い</option>
          </select>
        </div>

        <!-- 状態の詳細 -->
        <div class="form-group">
          <label class="form-label" for="inputConditionDetail">
            状態の詳細所見
            <span class="auto-badge">AI客観分析</span>
          </label>
          <input type="text" id="inputConditionDetail" class="form-input" placeholder="傷や汚れなどの状態メモ">
        </div>

        <!-- 販売価格 (企画書 P.12: 手数料5%自動計算) -->
        <div class="form-group">
          <label class="form-label" for="inputPrice">
            販売価格 (¥) <span class="required">*</span>
            <span class="auto-badge">AI推奨価格反映</span>
          </label>
          <input type="number" id="inputPrice" class="form-input" min="100" max="9999999" placeholder="例: 3980" required>
          <div class="price-calc-box">
            <div class="price-calc-row">
              <span>販売手数料 (5%)</span>
              <span id="feeAmount">- ¥199</span>
            </div>
            <div class="price-calc-row total">
              <span>販売利益 (受取金額)</span>
              <span class="profit-amount" id="profitAmount">¥3,781</span>
            </div>
          </div>
        </div>

        <!-- 商品説明文 -->
        <div class="form-group">
          <label class="form-label" for="inputDescription">
            商品の説明 <span class="required">*</span>
            <span class="auto-badge">AI自動生成</span>
          </label>
          <textarea id="inputDescription" class="form-textarea" placeholder="商品の特徴、購入時期、使用頻度などを記載してください" required></textarea>
        </div>

        <!-- タグ -->
        <div class="form-group">
          <label class="form-label">
            おすすめタグ
            <span class="auto-badge">AI抽出</span>
          </label>
          <div class="tags-container" id="tagsContainer"></div>
        </div>

        <!-- 出品確定ボタン -->
        <button type="submit" class="btn-submit-listing">この内容で出品する</button>
      </form>
    </div>
  </main>

  <!-- 出品完了モーダル (スライド P.9 準拠) -->
  <div class="modal-overlay" id="modalOverlay">
    <div class="modal-card">
      <div class="success-icon-circle">✓</div>
      <h3 class="modal-title">出品が完了しました！</h3>
      <p class="modal-desc">出品された商品は、タイムラインや商品一覧から購入者に閲覧されます。</p>
      <button type="button" class="btn-modal-close" id="btnModalClose">続けて出品する</button>
    </div>
  </div>

  <!-- 下部固定ナビゲーションバー (スライド P.9 準拠) -->
  <nav class="bottom-nav">
    <a href="#" class="nav-item">
      <span class="nav-icon">🏠</span>
      <span>ホーム</span>
    </a>
    <a href="#" class="nav-item">
      <span class="nav-icon">🔍</span>
      <span>探す</span>
    </a>
    <a href="#" class="nav-item active">
      <span class="nav-icon">➕</span>
      <span>出品</span>
    </a>
    <a href="#" class="nav-item">
      <span class="nav-icon">💬</span>
      <span>SNS</span>
    </a>
    <a href="#" class="nav-item">
      <span class="nav-icon">👤</span>
      <span>マイページ</span>
    </a>
  </nav>

  <script src="js/ai_assist.js"></script>
</body>
</html>
