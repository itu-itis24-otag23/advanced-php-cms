</div> <div class="sidebar-area">
        
        <div class="sidebar-widget">
            <h3>Hakkımda</h3>
            <p>Bu platform saf PHP ve MySQL kullanılarak sıfırdan geliştirilmiş, ilişkisel veritabanı mimarisine sahip gelişmiş bir İçerik Yönetim Sistemidir (CMS).</p>
        </div>

        <div class="sidebar-widget">
            <h3>Popüler Etiketler</h3>
            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 15px;">
                <?php
                // Footer'da listelemek için tüm etiketleri çekiyoruz
                // $db nesnesi header.php'de açıldığı için burada doğrudan erişebiliyoruz
                $sidebar_tags = $db->query("SELECT * FROM tags ORDER BY name ASC")->fetchAll();
                if (count($sidebar_tags) > 0) {
                    foreach ($sidebar_tags as $s_tag) {
                        echo "<a href='index.php?tag={$s_tag['slug']}' class='badge'>#{$s_tag['name']}</a>";
                    }
                } else {
                    echo "<span style='color:#aaa; font-size:14px;'>Etiket bulunmuyor.</span>";
                }
                ?>
            </div>
        </div>

    </div> </div> <footer style="background: #2c3e50; color: #bdc3c7; text-align: center; padding: 20px 0; margin-top: 60px; border-top: 4px solid #2ecc71;">
    <p>&copy; <?php echo date('Y'); ?> DevCMS - Tüm Hakları Saklıdır.</p>
</footer>

</body>
</html>