/**
 * MEETORY - AI出品アシスト フロントエンド スクリプト
 * 企画書 (P.8, P.9, P.12) の画面推移・インタラクションを制御
 */

document.addEventListener("DOMContentLoaded", () => {
  // DOM要素の取得
  const fileInput = document.getElementById("productImageInput");
  const dropzone = document.getElementById("uploadDropzone");
  const previewContainer = document.getElementById("previewContainer");
  const previewImg = document.getElementById("previewImg");
  const btnReselect = document.getElementById("btnReselect");

  const analyzingOverlay = document.getElementById("analyzingOverlay");
  const checkState = document.getElementById("checkState");
  const checkPrice = document.getElementById("checkPrice");
  const checkSuggest = document.getElementById("checkSuggest");

  const aiResultHero = document.getElementById("aiResultHero");
  const recommendedPriceDisplay = document.getElementById("recommendedPriceDisplay");
  const priceRangeDisplay = document.getElementById("priceRangeDisplay");
  const conditionBadge = document.getElementById("conditionBadge");
  const conditionDetailDisplay = document.getElementById("conditionDetailDisplay");
  const aiReasonDisplay = document.getElementById("aiReasonDisplay");
  const demoPill = document.getElementById("demoPill");

  const formCard = document.getElementById("formCard");
  const listingForm = document.getElementById("listingForm");
  const inputTitle = document.getElementById("inputTitle");
  const selectCategory = document.getElementById("selectCategory");
  const selectCondition = document.getElementById("selectCondition");
  const inputConditionDetail = document.getElementById("inputConditionDetail");
  const inputPrice = document.getElementById("inputPrice");
  const feeAmount = document.getElementById("feeAmount");
  const profitAmount = document.getElementById("profitAmount");
  const inputDescription = document.getElementById("inputDescription");
  const tagsContainer = document.getElementById("tagsContainer");

  const modalOverlay = document.getElementById("modalOverlay");
  const btnModalClose = document.getElementById("btnModalClose");

  const COMMISSION_RATE = 0.05; // 企画書 P.12: 販売手数料5%

  // 1. ドラッグ＆ドロップイベント
  ["dragenter", "dragover"].forEach((eventName) => {
    dropzone.addEventListener(eventName, (e) => {
      e.preventDefault();
      dropzone.classList.add("dragover");
    });
  });

  ["dragleave", "drop"].forEach((eventName) => {
    dropzone.addEventListener(eventName, (e) => {
      e.preventDefault();
      dropzone.classList.remove("dragover");
    });
  });

  dropzone.addEventListener("drop", (e) => {
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      handleImageSelected(e.dataTransfer.files[0]);
    }
  });

  dropzone.addEventListener("click", () => {
    fileInput.click();
  });

  fileInput.addEventListener("change", (e) => {
    if (e.target.files && e.target.files[0]) {
      handleImageSelected(e.target.files[0]);
    }
  });

  btnReselect.addEventListener("click", (e) => {
    e.stopPropagation();
    resetForm();
    fileInput.value = "";
    fileInput.click();
  });

  // 2. サンプル画像テストボタンのハンドラ (1クリックで体験可能)
  document.querySelectorAll(".sample-btn").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      const type = btn.getAttribute("data-sample");
      createSampleImageBlob(type).then((blob) => {
        handleImageSelected(blob, type);
      });
    });
  });

  // 3. 画像選択時の処理
  function handleImageSelected(fileOrBlob, sampleType = "") {
    // プレビュー表示
    const reader = new FileReader();
    reader.onload = (e) => {
      previewImg.src = e.target.result;
      previewContainer.style.display = "block";
      dropzone.style.display = "none";

      // AI分析処理を開始
      startAiAnalysis(fileOrBlob, sampleType);
    };
    reader.readAsDataURL(fileOrBlob);
  }

  // 4. AI分析リクエストとアニメーション (スライド P.9 演出)
  async function startAiAnalysis(fileOrBlob, sampleType = "") {
    // UIを分析中状態へ
    analyzingOverlay.style.display = "block";
    aiResultHero.style.display = "none";
    formCard.style.display = "none";

    checkState.classList.remove("done");
    checkPrice.classList.remove("done");
    checkSuggest.classList.remove("done");

    // アニメーション用ステップ進行タイマー
    const step1Timer = setTimeout(() => checkState.classList.add("done"), 600);
    const step2Timer = setTimeout(() => checkPrice.classList.add("done"), 1200);
    const step3Timer = setTimeout(() => checkSuggest.classList.add("done"), 1800);

    const formData = new FormData();
    formData.append("image", fileOrBlob, "product.jpg");
    if (sampleType) {
      formData.append("hint", sampleType);
    }

    try {
      const response = await fetch("api/ai_assist.php", {
        method: "POST",
        body: formData,
      });

      const res = await response.json();

      // 最低2秒間はアニメーションを見せることで「分析感」を演出
      await new Promise((resolve) => setTimeout(resolve, 2000));

      clearTimeout(step1Timer);
      clearTimeout(step2Timer);
      clearTimeout(step3Timer);
      checkState.classList.add("done");
      checkPrice.classList.add("done");
      checkSuggest.classList.add("done");

      await new Promise((resolve) => setTimeout(resolve, 500));
      analyzingOverlay.style.display = "none";

      if (res.success && res.data) {
        populateAiResults(res.data);
      } else {
        alert("AI分析中にエラーが発生しました: " + (res.error || "通信エラー"));
      }
    } catch (err) {
      console.error(err);
      analyzingOverlay.style.display = "none";
      alert("サーバーとの通信に失敗しました。PHPサーバーが稼働しているかご確認ください。");
    }
  }

  // 5. AI結果のフォーム反映
  function populateAiResults(data) {
    // デモバッジの表示制御
    if (data.is_demo) {
      demoPill.style.display = "inline-block";
      demoPill.textContent = "デモモード";
    } else {
      demoPill.style.display = "none";
    }

    // AI提案ハイライトカードの表示
    const recPrice = data.price_estimation?.recommended || 3980;
    const minPrice = data.price_estimation?.min || 3000;
    const maxPrice = data.price_estimation?.max || 5000;

    recommendedPriceDisplay.textContent = "¥" + recPrice.toLocaleString();
    priceRangeDisplay.textContent = `¥${minPrice.toLocaleString()} 〜 ¥${maxPrice.toLocaleString()}`;
    conditionBadge.textContent = data.condition || "目立った傷や汚れなし";
    conditionDetailDisplay.textContent = data.condition_detail || "目立った傷や汚れはなく良好です。";
    aiReasonDisplay.textContent = data.price_estimation?.reason || "相場目安および外観状態を考慮した推奨価格です。";

    aiResultHero.style.display = "block";

    // 入力フォームへの自動入力
    inputTitle.value = data.item_name || "";
    inputConditionDetail.value = data.condition_detail || "";
    inputPrice.value = recPrice;
    calculateProfits(recPrice);

    // カテゴリ自動選択
    if (data.category) {
      const options = Array.from(selectCategory.options);
      const match = options.find((opt) => data.category.includes(opt.value) || opt.value.includes(data.category));
      if (match) {
        selectCategory.value = match.value;
      }
    }

    // 状態自動選択
    if (data.condition) {
      const condOptions = Array.from(selectCondition.options);
      const match = condOptions.find((opt) => opt.value === data.condition);
      if (match) {
        selectCondition.value = match.value;
      }
    }

    // 説明文自動入力
    inputDescription.value = data.description || "";

    // タグ表示
    tagsContainer.innerHTML = "";
    if (Array.isArray(data.tags)) {
      data.tags.forEach((tag) => {
        const chip = document.createElement("span");
        chip.className = "tag-chip";
        chip.textContent = "# " + tag;
        tagsContainer.appendChild(chip);
      });
    }

    // フォーム表示 & スムーズスクロール
    formCard.style.display = "block";
    aiResultHero.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  // 6. 販売手数料 (5%) と販売利益のリアルタイム計算 (スライド P.12)
  function calculateProfits(price) {
    const p = parseInt(price, 10);
    if (isNaN(p) || p <= 0) {
      feeAmount.textContent = "- ¥0";
      profitAmount.textContent = "¥0";
      return;
    }
    const fee = Math.floor(p * COMMISSION_RATE);
    const profit = p - fee;

    feeAmount.textContent = `- ¥${fee.toLocaleString()}`;
    profitAmount.textContent = `¥${profit.toLocaleString()}`;
  }

  inputPrice.addEventListener("input", (e) => {
    calculateProfits(e.target.value);
  });

  // 7. 出品送信処理 (スライド P.9 の完了画面へ)
  listingForm.addEventListener("submit", (e) => {
    e.preventDefault();
    if (!inputTitle.value.trim()) {
      alert("商品名を入力してください");
      inputTitle.focus();
      return;
    }
    if (!inputPrice.value || inputPrice.value < 100) {
      alert("販売価格を正しく入力してください (最低100円〜)");
      inputPrice.focus();
      return;
    }

    // 出品完了モーダルを表示
    modalOverlay.style.display = "flex";
  });

  btnModalClose.addEventListener("click", () => {
    modalOverlay.style.display = "none";
    resetForm();
    window.scrollTo({ top: 0, behavior: "smooth" });
  });

  function resetForm() {
    previewContainer.style.display = "none";
    dropzone.style.display = "block";
    analyzingOverlay.style.display = "none";
    aiResultHero.style.display = "none";
    formCard.style.display = "none";
    listingForm.reset();
  }

  // テスト用サンプル画像 Blob 生成ヘルパー (手元に画像ファイルがなくても即テスト可能)
  function createSampleImageBlob(type) {
    return new Promise((resolve) => {
      const canvas = document.createElement("canvas");
      canvas.width = 400;
      canvas.height = 400;
      const ctx = canvas.getContext("2d");

      // 背景グラデーション
      const grad = ctx.createLinearGradient(0, 0, 400, 400);
      grad.addColorStop(0, "#e0f2fe");
      grad.addColorStop(1, "#c7d2fe");
      ctx.fillStyle = grad;
      ctx.fillRect(0, 0, 400, 400);

      // アイコン描画
      ctx.textAlign = "center";
      ctx.textBaseline = "middle";
      ctx.font = "80px sans-serif";

      let icon = "🎮";
      let title = "コントローラー";
      if (type.includes("ヘッドセット")) {
        icon = "🎧";
        title = "ゲーミングヘッドセット";
      } else if (type.includes("PC")) {
        icon = "💻";
        title = "ノートパソコン";
      } else if (type.includes("カメラ")) {
        icon = "📷";
        title = "デジタルカメラ";
      }

      ctx.fillText(icon, 200, 160);
      ctx.fillStyle = "#1e1b4b";
      ctx.font = "bold 22px sans-serif";
      ctx.fillText(title, 200, 260);

      ctx.fillStyle = "#6366f1";
      ctx.font = "14px sans-serif";
      ctx.fillText("MEETORY サンプル商品", 200, 300);

      canvas.toBlob((blob) => {
        resolve(blob);
      }, "image/jpeg", 0.9);
    });
  }
});
