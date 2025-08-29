document.addEventListener('DOMContentLoaded', () => {
    const csrfTokenInput = document.getElementById('csrf_token');
    const csrfToken = csrfTokenInput ? csrfTokenInput.value : '';

    document.querySelectorAll('button.remove-trade').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            const tr = btn.closest('tr');
            const id = btn.getAttribute('data-id');
            fetch('trades.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'remove', id, csrf_token: csrfToken }),
                credentials: 'same-origin'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    tr.remove();
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
