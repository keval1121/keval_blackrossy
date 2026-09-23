<div id="cookie-notice" class="fixed inset-x-0 bottom-20 z-50 hidden border-t border-line bg-cream/95 p-4 shadow-lg backdrop-blur md:bottom-4 md:left-4 md:right-auto md:max-w-md md:rounded-2xl md:border">
    <p class="text-sm leading-6 text-ink">
        We use cookies for cart, security and — when ads are enabled — advertising (including Google). See our
        <a class="underline" href="{{ url('/privacy-policy') }}">Privacy Policy</a>
        and
        <a class="underline" href="{{ url('/cookie-policy') }}">Cookie Policy</a>.
        Opt out of personalised ads:
        <a class="underline" href="https://adssettings.google.com" rel="noopener noreferrer" target="_blank">Google Ads Settings</a>.
    </p>
    <button type="button" id="cookie-notice-accept" class="btn btn-primary mt-3 w-full text-sm">Got it</button>
</div>
<script>
    (function () {
        try {
            if (localStorage.getItem('blackrossy_cookie_notice') === '1') {
                return;
            }
            var el = document.getElementById('cookie-notice');
            var btn = document.getElementById('cookie-notice-accept');
            if (!el || !btn) {
                return;
            }
            el.classList.remove('hidden');
            btn.addEventListener('click', function () {
                localStorage.setItem('blackrossy_cookie_notice', '1');
                el.classList.add('hidden');
            });
        } catch (e) {}
    })();
</script>
