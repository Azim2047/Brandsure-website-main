<?php
/**
 * Render every public surface to build/preview/ as a complete document, head
 * included, so the audits and the browser QA look at what a visitor would
 * actually receive rather than at a fragment.
 *
 * This is a HARNESS, not a renderer: it stubs the ~80 WordPress functions the
 * theme calls, then includes the real templates and the real components with
 * the real data files. So what it produces is the theme's own output, not a
 * mock of it — which is the only reason the audits that run against it mean
 * anything.
 *
 * It exists because the alternative is a WordPress install, and a WordPress
 * install cannot run in CI in under a second. Where a stub and WordPress would
 * disagree, the stub is wrong and the test is worthless, so stubs stay dumb:
 * esc_html really escapes, is_page really compares the slug, and nothing here
 * pretends to have a database.
 *
 * Written in Chat 12 as /tmp/bsr/render-all.php; moved into the repo in
 * Chat 17, because a test harness that lives in /tmp is a test harness that
 * exists until the next session and then does not.
 *
 * Usage:  php tools/render.php        (or `npm run render`)
 */

define('ABSPATH','/wp/');
define('BRANDSURE_VERSION','2.0.0-alpha');
define('BRANDSURE_DIR', dirname(__DIR__) . '/wp-content/themes/brandsure-v2');
define('BRANDSURE_URI','/wp-content/themes/brandsure-v2');
define('DAY_IN_SECONDS',86400);
define('WP_DEBUG', true);
define('BUILD_DIR', dirname(__DIR__) . '/build/preview');

$GLOBALS['__hooks'] = array();
$GLOBALS['__redirect'] = null;
$GLOBALS['__notices'] = array();

function esc_html($s){return htmlspecialchars((string)$s,ENT_QUOTES);}
function esc_attr($s){return htmlspecialchars((string)$s,ENT_QUOTES);}
function esc_url($s){return htmlspecialchars((string)$s,ENT_QUOTES);}
function esc_url_raw($s){return $s;}
function esc_html_e($s,$d=''){echo esc_html($s);}
function esc_attr_e($s,$d=''){echo esc_attr($s);}
function esc_html__($s,$d=''){return $s;}
function __($s,$d=''){return $s;}
function _x($s,$c,$d=''){return $s;}
function wp_kses_post($s){return $s;}
function wp_unslash($s){return $s;}
function wp_json_encode($d,$f=0){return json_encode($d,$f);}
function wp_parse_url($u,$c=-1){$p=parse_url($u); if($c===PHP_URL_PATH) return $p['path']??''; return $p;}
function get_transient($k){return false;}
function set_transient($k,$v,$t){return true;}
function delete_transient($k){return true;}
function get_current_user_id(){return 1;}
function home_url($p='/'){return 'https://brandsure.in'.$p;}
function user_trailingslashit($u){return rtrim($u,'/').'/';}
function get_template_directory_uri(){return BRANDSURE_URI;}
function sanitize_key($s){return preg_replace('/[^a-z0-9_\-]/','',strtolower((string)$s));}
function sanitize_html_class($s){return preg_replace('/[^A-Za-z0-9_\-]/','',(string)$s);}
function sanitize_title($s){return strtolower(preg_replace('/[^A-Za-z0-9]+/','-',trim((string)$s)));}
function sanitize_text_field($s){return trim(strip_tags((string)$s));}
function wp_strip_all_tags($s,$b=false){return trim(strip_tags((string)$s));}
function strip_shortcodes($s){return $s;}
function wp_trim_words($t,$n,$m='…'){$w=preg_split('/\s+/',trim($t)); return count($w)<=$n?$t:implode(' ',array_slice($w,0,$n)).$m;}
function is_admin(){return false;}
function wp_doing_ajax(){return false;}
function current_user_can($c){return false;}
function add_query_arg($k,$v=null,$u=null){
	$u = $u ?? '/';
	return $u . (str_contains($u,'?') ? '&' : '?') . rawurlencode(rawurldecode($k)) . '=' . rawurlencode(rawurldecode($v));
}
function get_option($k){
	if ($k==='page_on_front') return !empty($GLOBALS['__front']) ? 1 : 0;
	if ($k==='page_for_posts') return 99;
	return '';
}
function get_page_by_path($p){ return $GLOBALS['__pages'][$p] ?? null; }
function has_post_thumbnail(){return false;}
function get_the_post_thumbnail_url(){return '';}
function has_excerpt($p=null){$o=get_post($p); return $o && '' !== $o->post_excerpt;}
function get_the_date($f=''){return '2026-08-14T09:00:00+05:30';}
function get_the_modified_date($f=''){return '2026-08-20T11:30:00+05:30';}
function get_current_screen(){return null;}
function remove_post_type_support(){}
function remove_meta_box(){}

class WP_Post {
	public $ID = 7; public $post_type='page'; public $post_name='';
	public $post_title=''; public $post_content=''; public $post_excerpt='';
}

function get_post($p=null){ if($p instanceof WP_Post) return $p; return $GLOBALS['__post'] ?? null; }
function get_queried_object(){ return $GLOBALS['__post'] ?? null; }
function get_the_title($p=null){ if(is_int($p)) return 'Blog'; $o=get_post($p); return $o?$o->post_title:''; }
function get_permalink($p=null){
	if(is_int($p)) return 'https://brandsure.in/blog/';
	$o=get_post($p); if(!$o) return '';
	return $o->post_type==='post' ? 'https://brandsure.in/blog/'.$o->post_name.'/' : 'https://brandsure.in/'.$o->post_name.'/';
}

function is_front_page(){return !empty($GLOBALS['__front']);}
function is_home(){return !empty($GLOBALS['__blog']);}
function is_404(){return !empty($GLOBALS['__404']);}
function is_search(){return false;}
function is_paged(){return !empty($GLOBALS['__paged']);}
function is_singular($t=''){ $p=get_post(); if(!$p||is_home()||is_404()) return false; return ''===$t || $p->post_type===$t; }
function is_page($x=null){ $p=get_post(); if(!$p||$p->post_type!=='page'||is_home()||is_404()) return false; if(is_array($x)) return in_array($p->post_name,$x,true); if($x) return $p->post_name===$x; return true; }

function add_action($h,$f=null,$p=10,$a=1){ $GLOBALS['__hooks'][$h][$p][]=$f; }
function add_filter($h,$f=null,$p=10,$a=1){ $GLOBALS['__hooks'][$h][$p][]=$f; }
function remove_action($h,$f,$p=10){}
function remove_filter($h,$f,$p=10){}
function do_action($h,...$a){ $g=$GLOBALS['__hooks'][$h]??array(); ksort($g); foreach($g as $fs) foreach($fs as $f) $f(...$a); }
function apply_filters($h,$v,...$a){ $g=$GLOBALS['__hooks'][$h]??array(); ksort($g); foreach($g as $fs) foreach($fs as $f) $v=$f($v,...$a); return $v; }
function wp_robots_no_robots($r){ $r['noindex']=true; $r['follow']=true; return $r; }

function language_attributes(){ echo 'lang="en-IN"'; }
function bloginfo($x){ echo 'UTF-8'; }
function body_class($c=''){ echo 'class="'.esc_attr(trim($GLOBALS['__bodyclass'].' '.$c)).'"'; }
function wp_body_open(){}
function wp_footer(){
	printf('<script src="%s/assets/js/brandsure.js"></script>'."\n", BRANDSURE_URI);
}
function wp_head(){
	printf('<title>%s</title>'."\n", esc_html(apply_filters('pre_get_document_title','')));
	$robots = apply_filters('wp_robots', array());
	if ($robots) {
		$out = array();
		foreach ($robots as $k=>$v) $out[] = is_bool($v) ? $k : "$k:$v";
		printf('<meta name="robots" content="%s">'."\n", esc_attr(implode(', ',$out)));
	}
	do_action('wp_head');
	printf('<link rel="stylesheet" href="%s/assets/css/tokens.css">'."\n", BRANDSURE_URI);
	printf('<link rel="stylesheet" href="%s/assets/css/brandsure.css">'."\n", BRANDSURE_URI);
}
function wp_script_is($h,$s){ return 'brandsure'===$h; }
function wp_add_inline_script($h,$code,$pos='after'){ $GLOBALS['__inline'][]=$code; }

function get_template_part($slug,$name=null,$args=array()){
	$f=BRANDSURE_DIR.'/'.$slug.'.php';
	if(file_exists($f)) include $f; else echo "\n<!-- MISSING: $slug -->\n";
}
function get_header(){ include BRANDSURE_DIR.'/header.php'; }
function get_footer(){ include BRANDSURE_DIR.'/footer.php'; }

require BRANDSURE_DIR.'/inc/data.php';
require BRANDSURE_DIR.'/inc/content-ownership.php';
require BRANDSURE_DIR.'/inc/guard.php';
require BRANDSURE_DIR.'/inc/content-guard.php';
require BRANDSURE_DIR.'/inc/events.php';
require BRANDSURE_DIR.'/inc/helpers.php';
require BRANDSURE_DIR.'/inc/schema.php';
require BRANDSURE_DIR.'/inc/seo.php';
require BRANDSURE_DIR.'/inc/redirects.php';

/* The loop, for templates that run one. */
function have_posts(){ static $n=array(); $k=$GLOBALS['__slug']; if(!isset($n[$k])) $n[$k]=0; return $n[$k]++ < ($GLOBALS['__postcount'] ?? 1); }
function the_post(){ $GLOBALS['__i'] = ($GLOBALS['__i'] ?? -1) + 1; }
function the_title($b='',$a=''){ echo $b . esc_html(get_the_title()) . $a; }
function the_content(){ echo apply_filters('the_content', $GLOBALS['__post']->post_content); }
function the_permalink(){ echo esc_url(get_permalink()); }
function the_excerpt(){ echo '<p>'.esc_html($GLOBALS['__post']->post_excerpt).'</p>'; }
function get_the_excerpt($p=null){ $o=get_post($p); return $o?$o->post_excerpt:''; }
function wp_reset_postdata(){}
function the_posts_pagination($a=array()){ echo '<nav class="navigation pagination"><a class="page-numbers" href="/blog/page/2/">2</a></nav>'; }
function get_the_post_thumbnail($p=null,$s='',$a=array()){ return ''; }
function get_post_time($f='',$g=false,$p=null){ return strtotime('2026-08-14'); }
function esc_textarea($s){ return esc_html($s); }
function number_format_i18n($n){ return number_format($n); }

function bs_render(string $file, callable $setup): void {
	$GLOBALS['__post']=null; $GLOBALS['__front']=false; $GLOBALS['__blog']=false;
	$GLOBALS['__404']=false; $GLOBALS['__paged']=false; $GLOBALS['__bodyclass']='';
	$GLOBALS['__postcount']=1; $GLOBALS['__i']=-1;
	$template = $setup();
	ob_start();
	include BRANDSURE_DIR.'/'.$template;
	$html = ob_get_clean();
	file_put_contents(BUILD_DIR . '/' . $file, $html);
	printf("%-46s %7d bytes\n", $file, strlen($html));
}

function bs_setup(string $slug, string $title, string $type='page', string $content='', string $body=''): void {
	$p = new WP_Post();
	$p->post_type=$type; $p->post_name=$slug; $p->post_title=$title; $p->post_content=$content;
	$GLOBALS['__post']=$p;
	$GLOBALS['__slug']=$slug;
	$GLOBALS['__front']=('' === $slug && 'page'===$type);
	$GLOBALS['__bodyclass']=$body;
	$_SERVER['REQUEST_URI'] = '' === $slug ? '/' : ('post'===$type ? "/blog/$slug/" : "/$slug/");
}

@mkdir(BUILD_DIR, 0777, true);

/*
 * Pages reference their CSS, JS and the icon sprite at absolute paths under
 * /wp-content/. A SYMLINK rather than a copy, deliberately: a copy is a second
 * version of every asset that goes stale the moment somebody edits the real
 * one, and the whole point of the preview is to show what the theme currently
 * does. With the link, editing brandsure.css and refreshing is enough.
 */
$link = BUILD_DIR . '/wp-content';

if (!file_exists($link)) {
	@symlink(dirname(__DIR__) . '/wp-content', $link);
}

$controlled = array(
	'home.html'               => array('',                   'Home',               'front-page.php'),
	'plans.html'              => array('plans',              'Plans',              'templates/page-plans.php'),
	'digital-partner.html'    => array('digital-partner',    'Digital Partner',    'templates/page-digital-partner.php'),
	'growth-partnership.html' => array('growth-partnership', 'Growth Partnership', 'templates/page-growth-partnership.php'),
	'how-it-works.html'       => array('how-it-works',       'How It Works',       'templates/page-how-it-works.php'),
	'proof.html'              => array('proof',              'Proof',              'templates/page-proof.php'),
	'start.html'              => array('start',              'Start',              'templates/page-start.php'),
	'built-by-brandsure.html' => array('built-by-brandsure', 'Built by BrandSure', 'templates/page-built-by.php'),
	'services.html'           => array('services',           'Other Services',     'templates/page-services-index.php'),
);

foreach ($controlled as $file => $c) {
	bs_render($file, function() use ($c) { bs_setup($c[0], $c[1]); return $c[2]; });
}

$editable = array(
	'about.html' => array('about', 'About BrandSure', 'page.php'),
	'privacy-policy.html' => array('privacy-policy', 'Privacy Policy', 'page.php'),
	'cookie-policy.html' => array('cookie-policy', 'Cookie Policy', 'page.php'),
	'terms-and-conditions.html' => array('terms-and-conditions', 'Terms and Conditions', 'page.php'),
	'disclaimer.html' => array('disclaimer', 'Disclaimer', 'page.php'),
	'furniture-growth-marketing-india.html' => array('furniture-growth-marketing-india', 'Furniture Growth &amp; Marketing', 'templates/page-service.php'),
	'furniture-business-consulting-india.html' => array('furniture-business-consulting-india', 'Furniture Business Consulting', 'templates/page-service.php'),
	'furniture-product-design-services-india.html' => array('furniture-product-design-services-india', 'Furniture Product Design', 'templates/page-service.php'),
	'furniture-labour-hiring-india.html' => array('furniture-labour-hiring-india', 'Furniture Labour Hiring', 'templates/page-service.php'),
);

foreach ($editable as $file => $c) {
	bs_render($file, function() use ($c) {
		$seed = BRANDSURE_DIR.'/migration/'.$c[0].'.html';
		$content = is_readable($seed) ? preg_replace('/<!--\s*\/?wp:.*?-->/s','',file_get_contents($seed)) : '<p>Placeholder.</p>';
		bs_setup($c[0], html_entity_decode($c[1]), 'page', $content);
		return $c[2];
	});
}

// Blog index and a single post.
bs_render('blog.html', function() {
	bs_setup('blog', 'Blog', 'page');
	$GLOBALS['__blog']=true; $GLOBALS['__postcount']=3;
	$GLOBALS['__post']->post_type='post';
	$GLOBALS['__post']->post_name='why-furniture-enquiries-go-cold';
	$GLOBALS['__post']->post_title='Why furniture enquiries go cold before anyone calls back';
	$GLOBALS['__post']->post_excerpt='An enquiry answered in ten minutes behaves completely differently from the same enquiry answered the next day.';
	$_SERVER['REQUEST_URI']='/blog/';
	return 'templates/archive-blog.php';
});

bs_render('post.html', function() {
	bs_setup('why-furniture-enquiries-go-cold','Why furniture enquiries go cold before anyone calls back','post',
		'<p>An enquiry answered in ten minutes behaves completely differently from the same enquiry answered the next day. Most furniture businesses are not short of enquiries.</p>'.
		'<h2>What actually happens</h2>'.
		'<p>The message arrives on a Sunday afternoon. Nobody sees it until Monday. By Monday the customer has walked into two other showrooms.</p>');
	return 'templates/single-post.php';
});

bs_render('404.html', function() {
	$GLOBALS['__404']=true; $GLOBALS['__slug']='';
	$_SERVER['REQUEST_URI']='/no-such-page/';
	return '404.php';
});

echo "\nrendered ", count(glob(BUILD_DIR . '/*.html')), " surfaces\n";
