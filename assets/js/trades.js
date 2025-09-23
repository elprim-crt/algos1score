const init = () => {
    const csrfInput = document.querySelector('#addPairForm input[name="csrf_token"]');
    const csrfToken = csrfInput ? csrfInput.value : '';

    const updateCellColor = (td) => {
        const raw = td.textContent.trim();
        const match = raw.match(/-?\d+(\.\d+)?/);
        const value = match ? parseFloat(match[0]) : 0;
        const percent = Math.min(value, 10) * 10;
        const color = td.classList.contains('positive')
            ? 'rgba(76, 175, 80, 0.5)'
            : 'rgba(244, 67, 54, 0.5)';
        td.style.background = percent === 0
            ? '#fff'
            : `linear-gradient(to right, ${color} ${percent}%, #fff ${percent}%)`;
    };

    const dateInput = document.getElementById('date');
    const dateForm = document.getElementById('dateForm');
    const quickDate = document.getElementById('quickDate');

    if (quickDate && dateInput && dateForm) {
        quickDate.addEventListener('change', () => {
            const value = quickDate.value;
            if (!value) {
                return;
            }
            dateInput.value = value;
            if (typeof dateForm.requestSubmit === 'function') {
                dateForm.requestSubmit();
            } else {
                dateForm.submit();
            }
        });
    }

    document.querySelectorAll('td.positive, td.negative').forEach(updateCellColor);

    document.querySelectorAll('button.plus, button.minus').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            const tr = btn.closest('tr');
            const pair_id = tr.getAttribute('data-pair-id');
            const type = btn.getAttribute('data-type');
            const date = dateInput ? dateInput.value : '';
            fetch('trades.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'add', pair_id, type, date, csrf_token: csrfToken }),
                credentials: 'same-origin'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const cell = tr.querySelector('.' + type);
                    cell.textContent = data.count;
                    updateCellColor(cell);
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

    document.querySelectorAll('a.view-trades').forEach(link => {
        link.addEventListener('click', e => {
            const tr = link.closest('tr');
            const pair_id = tr.getAttribute('data-pair-id');
            const date = dateInput ? dateInput.value : '';
            link.href = `trades_view.php?pair_id=${encodeURIComponent(pair_id)}&date=${encodeURIComponent(date)}`;
        });
    });

    document.querySelectorAll('#tradesTable .remove-trade').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            const tr = btn.closest('tr');
            const id = btn.getAttribute('data-id');
            const pairId = tr ? tr.getAttribute('data-pair-id') : null;
            const type = tr ? tr.getAttribute('data-type') : null;
            fetch('trades.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'remove', id, csrf_token: csrfToken }),
                credentials: 'same-origin'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (pairId && type) {
                        const pairRow = document.querySelector(`#pairsTable tr[data-pair-id="${pairId}"]`);
                        if (pairRow) {
                            const cell = pairRow.querySelector(type === 'negative' ? '.negative' : '.positive');
                            if (cell) {
                                const current = parseInt(cell.textContent, 10) || 0;
                                const updated = Math.max(current - 1, 0);
                                cell.textContent = updated;
                                updateCellColor(cell);
                            }
                        }
                    }
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
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

