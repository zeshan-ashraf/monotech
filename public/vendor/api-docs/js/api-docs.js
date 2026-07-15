document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-copy-target]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var targetId = btn.getAttribute('data-copy-target');
            var el = document.getElementById(targetId);
            if (!el) return;
            copyText(el.textContent.trim(), btn);
        });
    });

    document.querySelectorAll('[data-copy-json]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-copy-json');
            var el = document.getElementById(id);
            if (!el) return;
            copyText(el.textContent.trim(), btn);
        });
    });

    function copyText(text, btn) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                flashCopied(btn);
            });
            return;
        }

        var textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        flashCopied(btn);
    }

    function flashCopied(btn) {
        var original = btn.innerHTML;
        btn.textContent = 'Copied!';
        setTimeout(function () {
            btn.innerHTML = original;
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        }, 1500);
    }

    if (typeof feather !== 'undefined') {
        feather.replace();
    }
});
