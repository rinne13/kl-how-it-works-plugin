<?php
/**
 * Plugin Name: KL – How It Works
 * Description: Редактируемый блок «Näin se toimii» с 4 шагами + шорткод [kl_how_it_works].
 * Version:     1.0.0
 * Author:      Varvara B.
 * Text Domain: koiranloma
 */

if ( ! defined('ABSPATH') ) exit;

class KL_How_It_Works {
    const OPT = 'kl_hiw_settings';

    public function __construct() {
        // defaults on first run
        add_action('admin_init', [$this, 'maybe_seed_defaults']);

        // settings page
        add_action('admin_menu',  [$this, 'admin_menu']);
        add_action('admin_init',  [$this, 'register_settings']);

        // assets
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
        add_action('wp_enqueue_scripts',    [$this, 'front_assets']);

        // shortcode
        add_shortcode('kl_how_it_works', [$this, 'shortcode']);
    }

    /** Seed defaults once */
    public function maybe_seed_defaults() {
        $opt = get_option(self::OPT);
        if ($opt !== false) return;

        $defaults = [
            'heading'    => 'Näin se toimii',
            'subheading' => 'Neljä selkeää askelta',
            'columns'    => 4,
            'steps'      => [
                [
                    'icon'  => 'chat',
                    'title' => 'Sovi tutustuminen',
                    'text'  => 'Ilmainen tapaaminen. Näet paikan ja kerrot lemmikin tavoista.',
                    'cta'   => ['label' => 'Varaa', 'url' => ''],
                ],
                [
                    'icon'  => 'check',
                    'title' => 'Sopikaa ehdot',
                    'text'  => 'Sovimme päivät ja hoitokohdat. Kaikki kirjallisesti ja selkeästi.',
                    'cta'   => ['label' => 'Lisätiedot', 'url' => ''],
                ],
                [
                    'icon'  => 'diamond',
                    'title' => 'Toi lemmikki',
                    'text'  => 'Leikki, ulkoilu ja kuvat WhatsAppissa. Ruoka tuodaan omana.',
                    'cta'   => ['label' => 'Ohjeet', 'url' => ''],
                ],
                [
                    'icon'  => 'wallet',
                    'title' => 'Maksu',
                    'text'  => 'Lasku hoidon jälkeen. Mahdollisuus jakaa kahteen erään.',
                    'cta'   => ['label' => 'Hinnasto', 'url' => ''],
                ],
            ],
        ];
        add_option(self::OPT, $defaults);
    }

    /** Admin menu */
  public function admin_menu() {
    add_submenu_page(
        'edit.php?post_type=palvelu',         
        __('Näin se toimii', 'koiranloma'),   
        __('Näin se toimii', 'koiranloma'),   
        'manage_options',
        'kl-hiw',
        [$this, 'render_settings_page']
    );
}


    /** Settings API */
    public function register_settings() {
        register_setting(self::OPT, self::OPT, [$this, 'sanitize']);

        add_settings_section('kl_hiw_main', __('Asetukset', 'koiranloma'), function(){
            echo '<p>'.esc_html__('Muokkaa osion sisältöä etusivulle.', 'koiranloma').'</p>';
        }, 'kl-hiw');

        add_settings_field('heading', __('Otsikko', 'koiranloma'), function(){
            $o = get_option(self::OPT);
            printf('<input type="text" name="%s[heading]" value="%s" class="regular-text" />',
                esc_attr(self::OPT), esc_attr($o['heading'] ?? ''));
        }, 'kl-hiw', 'kl_hiw_main');

        add_settings_field('subheading', __('Alaotsikko', 'koiranloma'), function(){
            $o = get_option(self::OPT);
            printf('<input type="text" name="%s[subheading]" value="%s" class="regular-text" />',
                esc_attr(self::OPT), esc_attr($o['subheading'] ?? ''));
        }, 'kl-hiw', 'kl_hiw_main');

        add_settings_field('columns', __('Sarakkeiden määrä', 'koiranloma'), function(){
            $o = get_option(self::OPT);
            $val = isset($o['columns']) ? (int)$o['columns'] : 4;
            echo '<select name="'.esc_attr(self::OPT).'[columns]">';
            foreach ([2,3,4] as $n) {
                printf('<option value="%d"%s>%d</option>', $n, selected($val,$n,false), $n);
            }
            echo '</select>';
        }, 'kl-hiw', 'kl_hiw_main');

        add_settings_field('steps', __('Vaiheet (4 kpl suositus)', 'koiranloma'), [$this,'steps_field'], 'kl-hiw', 'kl_hiw_main');
    }

    /** Steps repeater field */
    public function steps_field() {
        $o = get_option(self::OPT);
        $steps = is_array($o['steps'] ?? null) ? $o['steps'] : [];
        $icons = $this->icons();
        ?>
        <div id="kl-hiw-steps">
            <?php foreach ($steps as $i => $st): ?>
              <div class="kl-hiw-row">
                <div>
                  <label><?php esc_html_e('Ikoni', 'koiranloma'); ?></label>
                  <select name="<?php echo esc_attr(self::OPT); ?>[steps][<?php echo (int)$i; ?>][icon]">
                    <?php foreach ($icons as $key => $svg) : ?>
                      <option value="<?php echo esc_attr($key); ?>" <?php selected(($st['icon']??''),$key); ?>>
                        <?php echo esc_html($key); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label><?php esc_html_e('Otsikko', 'koiranloma'); ?></label>
                  <input type="text" name="<?php echo esc_attr(self::OPT); ?>[steps][<?php echo (int)$i; ?>][title]" value="<?php echo esc_attr($st['title']??''); ?>" />
                </div>
                <div>
                  <label><?php esc_html_e('Teksti', 'koiranloma'); ?></label>
                  <textarea name="<?php echo esc_attr(self::OPT); ?>[steps][<?php echo (int)$i; ?>][text]" rows="3"><?php echo esc_textarea($st['text']??''); ?></textarea>
                </div>
                <div class="kl-hiw-cta">
                  <label><?php esc_html_e('CTA', 'koiranloma'); ?></label>
                  <input type="text" placeholder="<?php esc_attr_e('Label', 'koiranloma'); ?>" name="<?php echo esc_attr(self::OPT); ?>[steps][<?php echo (int)$i; ?>][cta][label]" value="<?php echo esc_attr($st['cta']['label']??''); ?>" />
                  <input type="url"  placeholder="<?php esc_attr_e('URL', 'koiranloma'); ?>"   name="<?php echo esc_attr(self::OPT); ?>[steps][<?php echo (int)$i; ?>][cta][url]"   value="<?php echo esc_attr($st['cta']['url']??''); ?>" />
                </div>
                <button type="button" class="button link-delete" aria-label="<?php esc_attr_e('Remove', 'koiranloma'); ?>">✕</button>
              </div>
            <?php endforeach; ?>
        </div>
        <p><button type="button" class="button" id="kl-hiw-add"><?php esc_html_e('Lisää rivi', 'koiranloma'); ?></button></p>

        <template id="kl-hiw-row-template">
          <div class="kl-hiw-row">
            <div>
              <label><?php esc_html_e('Ikoni', 'koiranloma'); ?></label>
              <select name="<?php echo esc_attr(self::OPT); ?>[steps][__i__][icon]">
                <?php foreach ($icons as $key => $svg) : ?>
                  <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($key); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label><?php esc_html_e('Otsikko', 'koiranloma'); ?></label>
              <input type="text" name="<?php echo esc_attr(self::OPT); ?>[steps][__i__][title]" value="" />
            </div>
            <div>
              <label><?php esc_html_e('Teksti', 'koiranloma'); ?></label>
              <textarea name="<?php echo esc_attr(self::OPT); ?>[steps][__i__][text]" rows="3"></textarea>
            </div>
            <div class="kl-hiw-cta">
              <label><?php esc_html_e('CTA', 'koiranloma'); ?></label>
              <input type="text" placeholder="<?php esc_attr_e('Label', 'koiranloma'); ?>" name="<?php echo esc_attr(self::OPT); ?>[steps][__i__][cta][label]" />
              <input type="url"  placeholder="<?php esc_attr_e('URL', 'koiranloma'); ?>"   name="<?php echo esc_attr(self::OPT); ?>[steps][__i__][cta][url]" />
            </div>
            <button type="button" class="button link-delete" aria-label="<?php esc_attr_e('Remove', 'koiranloma'); ?>">✕</button>
          </div>
        </template>
        <?php
    }

    /** Sanitize */
    public function sanitize($input) {
        $out = [];
        $out['heading']    = sanitize_text_field($input['heading'] ?? '');
        $out['subheading'] = sanitize_text_field($input['subheading'] ?? '');
        $out['columns']    = max(2, min(4, (int)($input['columns'] ?? 4)));

        $out['steps'] = [];
        if (!empty($input['steps']) && is_array($input['steps'])) {
            foreach ($input['steps'] as $row) {
                $title = sanitize_text_field($row['title'] ?? '');
                $text  = wp_kses_post($row['text'] ?? '');
                if ($title === '' && $text === '') continue;

                $out['steps'][] = [
                    'icon'  => sanitize_key($row['icon'] ?? 'chat'),
                    'title' => $title,
                    'text'  => $text,
                    'cta'   => [
                        'label' => sanitize_text_field($row['cta']['label'] ?? ''),
                        'url'   => esc_url_raw($row['cta']['url'] ?? ''),
                    ],
                ];
            }
        }
        return $out;
    }

    /** Admin page */
    public function render_settings_page() {
        ?>
        <div class="wrap">
          <h1><?php esc_html_e('Näin se toimii', 'koiranloma'); ?></h1>
          <form method="post" action="options.php">
            <?php
              settings_fields(self::OPT);
              do_settings_sections('kl-hiw');
              submit_button();
            ?>
          </form>
          <p class="description">
            <?php esc_html_e('Lisää osio sivulle shortcodeilla:', 'koiranloma'); ?>
            <code>[kl_how_it_works]</code>
          </p>
        </div>
        <?php
    }

    /** Front styles */
    public function front_assets() {
        wp_enqueue_style('kl-hiw-style', plugins_url('assets/style.css', __FILE__), [], '1.0.0');
    }

    /** Admin assets */
   public function admin_assets($hook) {
    if (strpos($hook, 'kl-hiw') === false) return;

       wp_enqueue_script(
        'kl-hiw-admin',
        plugins_url('assets/admin.js', __FILE__),
        ['jquery'],
        '1.0.0',
        true
    );


       $css = '
      #kl-hiw-steps .kl-hiw-row{border:1px solid #e5e7eb;padding:12px;border-radius:8px;margin:10px 0;display:grid;grid-template-columns:1fr 1fr;gap:12px;position:relative;background:#fff}
      #kl-hiw-steps .kl-hiw-row .kl-hiw-cta{display:grid;grid-template-columns:1fr 1fr;gap:8px}
      #kl-hiw-steps .link-delete{position:absolute;right:8px;top:8px}
      @media (max-width:900px){#kl-hiw-steps .kl-hiw-row{grid-template-columns:1fr}}
    ';
    wp_register_style('kl-hiw-admin-style', false);
    wp_enqueue_style('kl-hiw-admin-style');
    wp_add_inline_style('kl-hiw-admin-style', $css);
    }

    /** Icons map (simple inline SVG) */
    private function icons() {
        return [
            'chat'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>',
            'check'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6L9 17l-5-5" fill="none" stroke="currentColor" stroke-width="2"/></svg>',
            'diamond' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h18l-9 14L3 7z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M7 7l5 14M17 7l-5 14" fill="none" stroke="currentColor" stroke-width="1.2"/></svg>',
            'wallet'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="16" cy="12" r="1.5" /></svg>',
        ];
    }

    /** Render function used by shortcode */
    public function render($atts = []) {
        $opt = get_option(self::OPT);
        $a = shortcode_atts([
            'heading'    => $opt['heading']    ?? '',
            'subheading' => $opt['subheading'] ?? '',
            'columns'    => (int)($opt['columns'] ?? 4),
        ], $atts, 'kl_how_it_works');

        $steps = is_array($opt['steps'] ?? null) ? $opt['steps'] : [];
        if (!$steps) return '';

        $icons = $this->icons();

       ob_start(); ?>
<section class="home-section kl-hiw">
  <div class="container">
    <header class="home-section__header">
      <?php if ($a['heading']) : ?>
        <h2 class="home-section__title"><?php echo esc_html($a['heading']); ?></h2>
      <?php endif; ?>
      <!-- справа можем вывести ссылку, если понадобится -->
      <?php if (!empty($a['subheading'])): ?>
        <span class="home-section__hint muted"><?php echo esc_html($a['subheading']); ?></span>
      <?php endif; ?>
    </header>

    <div class="kl-hiw__grid cols-<?php echo (int)$a['columns']; ?>">
      <?php foreach ($steps as $st): ?>
        <article class="kl-hiw__item">
          <div class="kl-hiw__icon" aria-hidden="true">
            <?php
              $key = sanitize_key($st['icon'] ?? 'chat');
              $svg = $icons[$key] ?? $icons['chat'];
              echo $svg;
            ?>
          </div>
          <?php if (!empty($st['title'])) : ?>
            <h3 class="kl-hiw__item-title"><?php echo esc_html($st['title']); ?></h3>
          <?php endif; ?>
          <?php if (!empty($st['text'])) : ?>
            <p class="kl-hiw__item-text"><?php echo esc_html($st['text']); ?></p>
          <?php endif; ?>
          <?php if (!empty($st['cta']['label']) && !empty($st['cta']['url'])) : ?>
            <p class="kl-hiw__item-cta">
              <a class="btn-cta" href="<?php echo esc_url($st['cta']['url']); ?>">
                <?php echo esc_html($st['cta']['label']); ?>
              </a>
            </p>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php
return ob_get_clean();

    }

    /** Shortcode callback */
    public function shortcode($atts) {
        return $this->render($atts);
    }
}

new KL_How_It_Works();
