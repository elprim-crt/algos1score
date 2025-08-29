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
                body: JSON.stringify({ action: 'add', pair_id, type, date, csrf_token: csrfToken }),
                credentials: 'same-origin'
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

    document.querySelectorAll('button.view-trades').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            const tr = btn.closest('tr');
            const pair_id = tr.getAttribute('data-pair-id');
            const date = document.getElementById('date').value;

            const tradeRows = [];
            let next = tr.nextElementSibling;
            while (next && next.classList.contains('trade-row')) {
                tradeRows.push(next);
                next = next.nextElementSibling;
            }

            if (tradeRows.length > 0) {
                const hidden = tradeRows[0].style.display === 'none';
                tradeRows.forEach(row => {
                    row.style.display = hidden ? 'table-row' : 'none';
                });
                return;
            }

            fetch('trades.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'list', pair_id, date, csrf_token: csrfToken }),
                credentials: 'same-origin'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const tbody = tr.parentNode;
                    const insertBefore = tr.nextSibling;
                    if (data.trades.length === 0) {
                        const row = document.createElement('tr');
                        row.className = 'trade-row';
                        const cell = document.createElement('td');
                        cell.colSpan = 4;
                        cell.textContent = 'No trades in this period.';
                        row.appendChild(cell);
                        tbody.insertBefore(row, insertBefore);
                        row.style.display = 'table-row';
                    } else {
                        data.trades.forEach(t => {
                            const row = document.createElement('tr');
                            row.className = 'trade-row';
                            const cell = document.createElement('td');
                            cell.colSpan = 4;
                            cell.textContent = `${t.date} - ${t.type}`;
                            row.appendChild(cell);
                            tbody.insertBefore(row, insertBefore);
                            row.style.display = 'table-row';
                        });
                    }
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
