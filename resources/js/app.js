import Swal from 'sweetalert2';
window.Swal = Swal;

document.addEventListener('DOMContentLoaded', () => {
    const navigation = document.querySelector('[data-mobile-navigation]');
    const openButton = document.querySelector('[data-open-navigation]');
    const closeButton = document.querySelector('[data-close-navigation]');

    if (navigation && openButton && closeButton) {
        const setOpen = (open) => {
            navigation.classList.toggle('hidden', !open);
            document.body.classList.toggle('overflow-hidden', open);
            if (open) {
                closeButton.focus();
            }
        };

        openButton.addEventListener('click', () => setOpen(true));
        closeButton.addEventListener('click', () => setOpen(false));
        navigation.addEventListener('click', (event) => {
            if (event.target === navigation) {
                setOpen(false);
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        });
    }

    // Dropdown kode produk mengikuti kode akad: pilihan produk dibatasi pada
    // produk dengan kode akad terpilih, dan sebaliknya memilih produk
    // menetapkan kode akadnya. Tanpa JavaScript, semua produk tetap tampil dan
    // validasi server tetap menolak kombinasi yang tidak cocok.
    document.querySelectorAll('[data-akad-select]').forEach((akadSelect) => {
        const form = akadSelect.closest('form');
        const produkSelect = form ? form.querySelector('[data-produk-select]') : null;

        if (!produkSelect) {
            return;
        }

        const options = Array.from(produkSelect.options);

        const filterProduk = () => {
            const akad = akadSelect.value;
            let selectedTerpakai = produkSelect.value === '';

            options.forEach((option) => {
                if (option.value === '') {
                    return;
                }

                const cocok = akad === '' || option.dataset.akad === akad;
                option.hidden = !cocok;
                option.disabled = !cocok;

                if (cocok && option.selected) {
                    selectedTerpakai = true;
                }
            });

            if (!selectedTerpakai) {
                produkSelect.value = '';
            }
        };

        akadSelect.addEventListener('change', filterProduk);

        produkSelect.addEventListener('change', () => {
            const akad = produkSelect.selectedOptions[0]?.dataset.akad;

            if (akad) {
                akadSelect.value = akad;
                filterProduk();
            }
        });

        filterProduk();
    });
});
