</main>
<footer>
    <p>Proyecto de ejemplo - PHP y MySQL</p>
    <!-- Muestra qué instancia atendió la petición: sirve para comprobar el balanceador -->
    <p class="servidor">Atendido por: <strong><?= e(gethostname()) ?></strong>
        (<?= e($_SERVER['SERVER_ADDR'] ?? '') ?>)</p>
</footer>
</body>
</html>
