<?php
/**
 * MEETORY - 設定ファイル
 * 企画書 (P.12, P.13) に基づく基本設定
 */

// エラー表示設定 (開発環境用)
ini_set('display_errors', '1');
error_reporting(E_ALL);

// Gemini API Key の取得 (環境変数 または 直接設定)
// ※ 本番運用の際は環境変数 GEMINI_API_KEY に設定するか、ここに直接キーを記述します
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');

// Gemini API のエンドポイント (Gemini 2.5 Flash)
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent');

// APIキー未設定時にデモ用データを返すフォールバック設定 (true: UI動作確認を優先)
define('ENABLE_DEMO_FALLBACK', true);

// 販売手数料率 (企画書 P.12: 販売手数料5%)
define('COMMISSION_RATE', 0.05);

// アップロード設定
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);
