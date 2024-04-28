import './bootstrap';
import iziToast from "izitoast";

document.addEventListener('DOMContentLoaded', function () {
    const forms = document.querySelectorAll('.api_form');
    for (let i = 0; i < forms.length; i++) {
        forms[i].addEventListener('submit', function (e) {
            e.preventDefault();

            fadeInElement('.spinner__overlay', 300);

            let form = this;
            let formData = new FormData(this);
            let action = form.action;

            api_axios(action, formData, form);
        });
    }
});

const api_axios = (action, formData, form) => {
    axios
        .post(action, formData)
        .then(function (response) {
            if (response.data.success) {
                showSuccessToast(response.data.message);
            } else {
                showErrorToast(response.data.message);
            }

            document.querySelector('#tryCount').innerText = response.data.tryCount;

            fadeOutElement('.spinner__overlay', 300);
        })
        .catch(function (error) {
            showErrorToast(error.message);
            fadeOutElement('.spinner__overlay', 300);
        });
};

const fadeInElement = (elementSelector, duration) => {
    const element = document.querySelector(elementSelector);
    if (!element) {
        console.error(`Element with selector "${elementSelector}" not found.`);
        return;
    }

    element.style.display = 'block'; // 初めに要素を表示
    element.style.opacity = 0; // 初めに透明度を0に設定

    let currentOpacity = 0;

    function fadeIn(timestamp) {
        currentOpacity += 1 / (duration / 10); // 10ミリ秒ごとに透明度を増加させる
        element.style.opacity = currentOpacity;

        if (currentOpacity < 1) {
            requestAnimationFrame(fadeIn); // フェードインが完了するまで再帰的に呼び出す
        }
    }

    fadeIn();
};

const fadeOutElement = (elementSelector, duration) => {
    const element = document.querySelector(elementSelector);
    if (!element) {
        console.error(`Element with selector "${elementSelector}" not found.`);
        return;
    }

    let startTime = null;

    function fade(timestamp) {
        if (!startTime) {
            startTime = timestamp;
        }

        const elapsed = timestamp - startTime;
        const opacity = Math.max(0, 1 - elapsed / duration);

        element.style.opacity = opacity;

        if (opacity > 0) {
            requestAnimationFrame(fade); // フェードが完了するまで再帰的に呼び出す
        } else {
            element.style.display = 'none'; // フェードアウト完了後、要素を非表示にする
        }
    }

    // フェードアウトアニメーションを開始
    requestAnimationFrame(fade);
};

const showErrorToast = (message) => {
    iziToast.error({
        title: 'Error',
        message: message ?? 'エラーが発生しました。',
        position: 'bottomRight',
        closeOnClick: true,
        timeout: 10000,
    });
};

const showSuccessToast = (message) => {
    iziToast.success({
        title: 'OK',
        message: message ?? 'データを正常に更新しました。',
        position: 'bottomRight',
        timeout: 10000,
        closeOnEscape: true,
        closeOnClick: true,
    });
};
