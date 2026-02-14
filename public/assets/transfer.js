// Debounced lookup for receiver username and phone
(() => {
    const userInput = document.getElementById('receiver_username');
    const phoneInput = document.getElementById('receiver_phone');
    const hint = document.getElementById('receiver_hint');
    const hiddenId = document.getElementById('receiver_id');
    if (!userInput || !phoneInput) return;

    let tUser = null;
    let tPhone = null;

    async function lookup(q) {
        if (!q) return null;
        try {
            const res = await fetch('/user_lookup.php?q=' + encodeURIComponent(q));
            const data = await res.json();
            return data;
        } catch (e) {
            return { error: true };
        }
    }

    userInput.addEventListener('input', () => {
        clearTimeout(tUser);
        hiddenId.value = '';
        hint.textContent = '';
        tUser = setTimeout(async () => {
            const q = userInput.value.trim();
            if (q === '') { hint.textContent = ''; phoneInput.value = phoneInput.value; return; }
            hint.textContent = 'Searching...';
            const data = await lookup(q);
            if (data && data.found) {
                hiddenId.value = data.id;
                phoneInput.value = data.phone || '';
                hint.textContent = 'Matched: ' + data.username + ' (' + (data.phone || 'no phone') + ')';
            } else if (data && data.error) {
                hint.textContent = 'Lookup error';
            } else {
                hint.textContent = 'No user found';
            }
        }, 300);
    });

    phoneInput.addEventListener('input', () => {
        clearTimeout(tPhone);
        hiddenId.value = '';
        hint.textContent = '';
        tPhone = setTimeout(async () => {
            const q = phoneInput.value.trim();
            if (q === '') { hint.textContent = ''; return; }
            if (!/^[0-9]{10}$/.test(q)) { hint.textContent = 'Enter 10-digit phone'; return; }
            hint.textContent = 'Searching...';
            const data = await lookup(q);
            if (data && data.found) {
                hiddenId.value = data.id;
                userInput.value = data.username || '';
                hint.textContent = 'Matched: ' + data.username + ' (' + (data.phone || 'no phone') + ')';
            } else if (data && data.error) {
                hint.textContent = 'Lookup error';
            } else {
                hint.textContent = 'No user found';
            }
        }, 300);
    });
})();
