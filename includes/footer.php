</div>
</div>
<script src="../public/assets/js/alerts.js"></script>
<script>
    /**
     * Toggles the UI theme between Light and Dark
     */
    function toggleTheme() {
        const html = document.documentElement;
        const currentTheme = html.classList.contains('dark') ? 'dark' : 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        // 1. Update UI immediately for responsiveness
        html.classList.remove('light', 'dark');
        html.classList.add(newTheme);

        // Update body classes if needed
        if (newTheme === 'dark') {
            document.body.classList.add('bg-gray-900', 'text-white');
            document.body.classList.remove('bg-gray-100', 'text-black');
        } else {
            document.body.classList.add('bg-gray-100', 'text-black');
            document.body.classList.remove('bg-gray-900', 'text-white');
        }

        // 2. Save to database
        savePref({ theme: newTheme });
    }

    /**
     * Changes the system language and reloads
     */
    function setLanguage(lang) {
        // Show a loading indicator (using SweetAlert2 if available)
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Please wait...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
        }

        savePref({ language: lang })
            .then(() => {
                window.location.reload();
            })
            .catch(err => {
                console.error('Error saving preference:', err);
            });
    }

    /**
     * Sends the preference to the server via AJAX
     */
    function savePref(data) {
        // Using a relative path to ensure it works in all subdirectories
        // Make sure this path correctly points to your preference handler
        const savePath = '../admin/preferences.php';

        return fetch(savePath, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            // We use URLSearchParams because your PHP expects $_POST from a standard form
            body: new URLSearchParams(data)
        });
    }
</script>

</body>

</html>