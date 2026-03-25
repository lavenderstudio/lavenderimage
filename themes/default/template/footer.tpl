<div id="copyright">
  {if isset($debug.TIME)}
    {'Page generated in'|translate} {$debug.TIME} ({$debug.NB_QUERIES} {'SQL queries in'|translate} {$debug.SQL_TIME}) -
  {/if}

{*
    Please, do not remove this copyright. If you really want to,
    contact us on http://piwigo.org to find a solution on how
    to show the origin of the script...
*}

  {'Powered by'|translate}	<a href="{$PHPWG_URL}" class="Piwigo">Piwigo</a>
  {$VERSION}
  {if isset($CONTACT_MAIL)}
  - <a href="mailto:{$CONTACT_MAIL}?subject={'A comment on your site'|translate|@escape:url}">{'Contact webmaster'|translate}</a>
  {/if}
  {if isset($TOGGLE_MOBILE_THEME_URL)}
  - {'View in'|translate} : <a href="{$TOGGLE_MOBILE_THEME_URL}">{'Mobile'|translate}</a> | <b>{'Desktop'|translate}</b>
  {/if}
  
  {if isset($footer_elements)}
  {foreach from=$footer_elements item=elt}
    {$elt}
  {/foreach}
  {/if}
</div>{* <!-- copyright --> *}

{if isset($debug.QUERIES_LIST)}
<div id="debug">
  {$debug.QUERIES_LIST}
</div>
{/if}
</div>{* <!-- the_page --> *}

<!-- BEGIN get_combined -->
{get_combined_scripts load='footer'}
<!-- END get_combined -->

</body>
</html>

<style>
  /* 1. Xóa bỏ footer mặc định */
  #copyright, .footer, footer, #footer, .footer_content, .text-center.padding-bottom { 
    display: none !important; 
    visibility: hidden !important; 
    height: 0 !important; 
    margin: 0 !important;
    padding: 0 !important;
  }

  /* 2. Thiết kế Lavender Prime Tràn viền */
  #lavender-prime-footer {
    width: 100vw !important;
    position: relative !important;
    left: 50% !important;
    transform: translateX(-50%) !important;
    background: #ffffff !important;
    border-top: 1px solid #f2f2f2 !important;
    padding: 120px 0 !important;
    text-align: center !important;
    z-index: 9999 !important;
    display: block !important;
    clear: both !important;
    margin-top: 100px !important;
    box-sizing: border-box !important;
  }
  .lavender-links { margin-bottom: 50px !important; }
  .lavender-links a { 
    color: #999 !important; 
    text-decoration: none !important; 
    margin: 0 20px !important; 
    font-size: 11px !important; 
    letter-spacing: 3px !important; 
    text-transform: uppercase !important;
    font-family: sans-serif !important;
  }
  .lavender-links a:hover { color: #000 !important; }
  .lavender-title { 
    font-family: serif !important; 
    font-size: 38px !important; 
    letter-spacing: 18px !important; 
    color: #111 !important; 
    text-transform: uppercase !important; 
    font-weight: 200 !important;
    margin: 25px 0 !important;
    border: none !important;
    line-height: 1.2 !important;
  }
  .lavender-subtitle { 
    font-size: 10px !important; 
    letter-spacing: 7px !important; 
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
