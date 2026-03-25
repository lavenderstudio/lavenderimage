<style>
  /* 1. Ẩn Footer mặc định của Piwigo */
  #copyright, .footer, footer, #footer, .footer_content { 
    display: none !important; 
    visibility: hidden !important; 
    height: 0 !important; 
  }

  /* 2. Thiết kế Lavender Prime (Museum Style) */
  #lavender-prime-footer {
    width: 100vw !important;
    position: relative !important;
    left: 50% !important;
    transform: translateX(-50%) !important;
    background: #ffffff !important;
    border-top: 1px solid #f2f2f2 !important;
    padding: 100px 0 !important;
    text-align: center !important;
    z-index: 9999 !important;
    display: block !important;
    clear: both !important;
    margin-top: 50px !important;
    box-sizing: border-box !important;
  }
  .lavender-links { margin-bottom: 40px !important; }
  .lavender-links a { 
    color: #999 !important; 
    text-decoration: none !important; 
    margin: 0 15px !important; 
    font-size: 11px !important; 
    letter-spacing: 2px !important; 
    font-family: sans-serif !important;
    text-transform: uppercase !important;
  }
  .lavender-title { 
    font-family: serif !important; 
    font-size: clamp(24px, 5vw, 36px) !important; 
    letter-spacing: 12px !important; 
    color: #111 !important; 
    text-transform: uppercase !important; 
    font-weight: 200 !important;
    margin: 20px 0 !important;
    border: none !important;
  }
  .lavender-subtitle { 
    font-size: 9px !important; 
    letter-spacing: 5px !important; 
    color: #ccc !important; 
    text-transform: uppercase !important; 
    font-family: sans-serif !important;
  }
</style>

<footer id="lavender-prime-footer">
    <div class="lavender-links">
        <a href="index.php">Albums</a>
        <a href="index.php?/recent_pics">Latest</a>
        <a href="index.php?/most_visited">Popular</a>
        <a href="index.php?/tags">Tags</a>
    </div>
    <div class="lavender-title">LAVENDER PRIME</div>
    <div class="lavender-subtitle">EST. 2026 | FINE ART DIGITAL GALLERY</div>
</footer>

<div id="copyright" style="display:none;">
  {if isset($debug.TIME)}
    {'Page generated in'|translate} {$debug.TIME} ({$debug.NB_QUERIES} {'SQL queries in'|translate} {$debug.SQL_TIME}) -
  {/if}
  {'Powered by'|translate} <a href="{$PHPWG_URL}" class="Piwigo">Piwigo</a> {$VERSION}
</div>

{if isset($debug.QUERIES_LIST)}
<div id="debug">{$debug.QUERIES_LIST}</div>
{/if}

</div> {* Kết thúc the_page *}

{get_combined_scripts load='footer'}

</body>
</html>
