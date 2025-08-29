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

    document.querySelectorAll('td.pair-name').forEach(td => {
        td.addEventListener('click', () => {
            const tr = td.parentElement;
            const drawer = tr.nextElementSibling;
            const pair_id = tr.getAttribute('data-pair-id');
            const date = document.getElementById('date').value;

            if (drawer.style.display === 'table-row') {
                drawer.style.display = 'none';
                return;
            }

            if (!drawer.dataset.loaded) {
                fetch('trades.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'list', pair_id, date, csrf_token: csrfToken })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const ul = document.createElement('ul');
                        data.trades.forEach(t => {
                            const li = document.createElement('li');
                            li.textContent = `${t.date} - ${t.type}`;
                            ul.appendChild(li);
                        });
                        drawer.querySelector('.trades-cell').appendChild(ul);
                        drawer.dataset.loaded = 'true';
                    } else {
                        alert('Error: ' + data.error);
                    }
                    drawer.style.display = 'table-row';
                })
                .catch(err => {
                    console.error('Fetch error:', err);
                    alert('An error occurred while communicating with the server. Please try again later.');
                });
            } else {
                drawer.style.display = 'table-row';
            }
        });
    });
});
