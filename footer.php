<?php if (is_logged_in()): ?>
    </main>
</div>
<?php else: ?>
</main>
<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", function () {

    // Standard date picker
    flatpickr(".date-picker", {
        dateFormat: "Y-m-d",
        defaultDate: "today"
    });

    // Date range picker
    flatpickr(".date-range-picker", {
        mode: "range",
        dateFormat: "Y-m-d",
        defaultDate: null
    });

    // Month picker for budgets
    flatpickr(".month-picker", {
        plugins: [
            new monthSelectPlugin({
                shorthand: true,
                dateFormat: "Y-m",
                altFormat: "F Y"
            })
        ]
    });

    // Responsive charts
    const chartContainers = document.querySelectorAll(".chart-card canvas");

    chartContainers.forEach(chart => {
        chart.style.maxWidth = "100%";
        chart.style.height = "auto";
    });

});
</script>

</body>
</html>