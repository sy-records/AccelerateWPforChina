<?php
/*
Plugin Name: Accelerate WP for China
Plugin URI: https://github.com/sy-records/AccelerateWPforChina
Description: 旨在为WordPress中国用户提供加速，加快站点更新版本、安装升级插件主题的速度，替换Gravatar头像链接。
Version: 1.0.0
Author: 沈唁
Author URI: https://qq52o.me
License: Apache 2.0
*/

define('ETUWPIC_BASEFOLDER', plugin_basename(dirname(__FILE__)));

register_activation_hook(__FILE__, 'awfc_set_options');
function awfc_set_options()
{
    $options = array(
        'custom_api_server' => "",
        'custom_download_server' => "",
        'custom_gravatar_server' => "",
    );
    add_option('awfc_options', $options, '', 'yes');
}

function awfc_pre_http_request($preempt, $parsed_args, $url)
{
    if (!stristr($url, 'api.wordpress.org') && !stristr($url, 'downloads.wordpress.org')) {
        return false;
    }

    $options = get_option('awfc_options');

    $url = str_replace('api.wordpress.org', $options["custom_api_server"], $url);
    $url = str_replace('downloads.wordpress.org', $options["custom_download_server"], $url);

    return wp_remote_request($url, $parsed_args);
}

$options = get_option('awfc_options');
if (!empty($options["custom_api_server"]) || !empty($options["custom_download_server"])) {
    add_filter('pre_http_request', 'awfc_pre_http_request', 10, 3);
} elseif (!empty($options['custom_gravatar_server'])) {
    add_filter('get_avatar', 'awfc_unblock_gravatar');
}

function awfc_unblock_gravatar($avatar)
{
    $options = get_option('awfc_options');
    $avatar = str_replace('https://secure.gravatar.com/avatar', $options['custom_gravatar_server'], $avatar);
    return $avatar;
}

function awfc_plugin_action_links($links, $file)
{
    if ($file == plugin_basename(dirname(__FILE__) . '/accelerate-wp-for-china.php')) {
        $links[] = '<a href="options-general.php?page=' . ETUWPIC_BASEFOLDER . '/accelerate-wp-for-china.php">设置</a>';
    }
    return $links;
}

add_filter('plugin_action_links', 'awfc_plugin_action_links', 10, 2);

function awfc_add_setting_page()
{
    add_options_page('Accelerate WP加速设置', 'Accelerate WP设置', 'manage_options', __FILE__, 'awfc_setting_page');
}

add_action('admin_menu', 'awfc_add_setting_page');

function awfc_setting_page()
{
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient privileges!');
    }
    $options = array();
    if (!empty($_POST) and $_POST['type'] == 'awfc_set') {
        $options['custom_api_server'] = isset($_POST['custom_api_server']) ? sanitize_text_field($_POST['custom_api_server']) : '';
        $options['custom_download_server'] = isset($_POST['custom_download_server']) ? sanitize_text_field($_POST['custom_download_server']) : '';
        $options['custom_gravatar_server'] = isset($_POST['custom_gravatar_server']) ? rtrim(sanitize_text_field($_POST['custom_gravatar_server']),"/") : '';
    }

    if ($options !== array()) {
        update_option('awfc_options', $options);
        echo '<div class="updated"><p><strong>设置已保存！</strong></p></div>';
    }

    $awfc_options = get_option('awfc_options', true);

    $awfc_custom_api_server = esc_attr($awfc_options['custom_api_server']);
    $awfc_custom_download_server = esc_attr($awfc_options['custom_download_server']);
    $awfc_custom_gravatar_server = esc_attr($awfc_options['custom_gravatar_server']);
    ?>
    <div class="wrap" style="margin: 10px;">
        <h1>Accelerate WP for China设置</h1>
        <p>Server 节点选择请参考 <a href="https://github.com/sy-records/EasyToUseWordPressInChina" target="_blank">Github</a></p>
        <form name="form" method="post" action="<?php echo wp_nonce_url('./options-general.php?page=' . ETUWPIC_BASEFOLDER . '/accelerate-wp-for-china.php'); ?>">
            <table class="form-table">
                <tr>
                    <th>
                        <legend>API Server</legend>
                    </th>
                    <td><input type="text" name="custom_api_server" value="<?php echo $awfc_custom_api_server; ?>" size="50" placeholder="API Server"/></td>
                </tr>
                <tr>
                    <th>
                        <legend>Download Server</legend>
                    </th>
                    <td>
                        <input type="text" name="custom_download_server" value="<?php echo $awfc_custom_download_server; ?>" size="50" placeholder="Download Server"/>
                    </td>
                </tr>
                <tr>
                    <th>
                        <legend>Gravatar Server</legend>
                    </th>
                    <td>
                        <input type="text" name="custom_gravatar_server" value="<?php echo $awfc_custom_gravatar_server; ?>" size="50" placeholder="Gravatar Server"/>
                    </td>
                </tr>
                <tr>
                    <th>
                        <legend>保存/更新选项</legend>
                    </th>
                    <td><input type="submit" name="submit" class="button button-primary" value="保存更改"/></td>
                </tr>
            </table>
            <input type="hidden" name="type" value="awfc_set">
        </form>
    </div>
<?php
}
?>
