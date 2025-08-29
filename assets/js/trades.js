document.addEventListener('DOMContentLoaded', () => {
    const csrfInput = document.querySelector('#addPairForm input[name="csrf_token"]');
    const csrfToken = csrfInput ? csrfInput.value : '';

    document.querySelectorAll('button.plus, button.minus').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            const tr = btn.closest('tr');
            const pair_id = tr.getAttribute('data-pair-id');
            const type = btn.getAttribute('data-type');
            const date = document.getElementById('date').value;
            fetch('trades.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ action: 'add', pair_id, type, date, csrf_token: csrfToken })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    tr.querySelector('.' + type).textContent = data.count;
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(err => {
                console.error('Fetch error:', err);
                alert('An error occurred while communicating with the server. Please try again later.');
            });
        });
    });
});
