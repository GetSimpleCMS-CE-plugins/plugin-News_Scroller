<?php
/*
Plugin Name: GS News Scroller
Description: Manage multiple news items and display them as animated scrolling marquees on your site.
Version: 1.0
Author: Zeth
*/

$thisfile = basename(__FILE__, ".php");

register_plugin(
    $thisfile,
    'News Scroller',
    '1.0',
    'Zeth',
    'https://getsimple-ce.ovh/',
    'Manage and display multiple news ticker marquees.',
    'pages',
    'gs_news_scroller_admin'
);

add_action('pages-sidebar', 'createSideMenu', array($thisfile, 'News Scroller'));
add_filter('content', 'gs_news_scroller_shortcode_filter');
add_filter('content-out', 'gs_news_scroller_shortcode_filter');

if (!defined('GS_NEWS_SCROLLER_DATA_PATH')) {
    define('GS_NEWS_SCROLLER_DATA_PATH', GSDATAOTHERPATH . 'gs_news_scroller/');
}

function gs_news_scroller_admin() {
    // Ensure data directory exists
    if (!file_exists(GS_NEWS_SCROLLER_DATA_PATH)) {
        mkdir(GS_NEWS_SCROLLER_DATA_PATH, 0755, true);
    }

    $selected_scroller = isset($_REQUEST['scroller_id']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', $_REQUEST['scroller_id']) : '';

    // Handle creation / switching of scroller
    if (isset($_POST['create_scroller'])) {
        $new_id = trim($_POST['new_scroller_id']);
        $new_id = preg_replace('/[^a-zA-Z0-9_\-]/', '', $new_id);
        if (!empty($new_id)) {
            $selected_scroller = $new_id;
            $file = GS_NEWS_SCROLLER_DATA_PATH . $selected_scroller . '.xml';
            if (!file_exists($file)) {
                $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><scroller_data><direction>left</direction><speed>25</speed><bg_color>#222222</bg_color><title_color>#ffca28</title_color><text_color>#ffffff</text_color><image_size>35</image_size><title_size>14</title_size><text_size>14</text_size><news_items></news_items></scroller_data>');
                $xml->asXML($file);
            }
            echo '<div class="updated" style="display:block;">Scroller created/selected successfully!</div>';
        }
    }

    // Handle deletion of the selected scroller
    if (isset($_POST['delete_scroller']) && !empty($selected_scroller)) {
        $file = GS_NEWS_SCROLLER_DATA_PATH . $selected_scroller . '.xml';
        if (file_exists($file)) {
            @unlink($file);
            echo '<div class="updated" style="display:block;">Scroller "' . htmlspecialchars($selected_scroller) . '" deleted successfully!</div>';
            $selected_scroller = ''; // Reset selection
        }
    }

    // Handle saving news and settings
    if (isset($_POST['save_news']) && !empty($selected_scroller)) {
        $data_file = GS_NEWS_SCROLLER_DATA_PATH . $selected_scroller . '.xml';
        
        $direction   = isset($_POST['scroller_direction']) && $_POST['scroller_direction'] === 'right' ? 'right' : 'left';
        $speed       = isset($_POST['scroller_speed']) ? intval($_POST['scroller_speed']) : 25;
        if ($speed <= 0) { $speed = 25; }

        $bg_color    = isset($_POST['bg_color']) ? htmlspecialchars($_POST['bg_color'], ENT_QUOTES, 'UTF-8') : '#222222';
        $title_color = isset($_POST['title_color']) ? htmlspecialchars($_POST['title_color'], ENT_QUOTES, 'UTF-8') : '#ffca28';
        $text_color  = isset($_POST['text_color']) ? htmlspecialchars($_POST['text_color'], ENT_QUOTES, 'UTF-8') : '#ffffff';
        $image_size  = isset($_POST['image_size']) ? intval($_POST['image_size']) : 35;
        if ($image_size <= 0) { $image_size = 35; }
        
        $title_size  = isset($_POST['title_size']) ? intval($_POST['title_size']) : 14;
        if ($title_size <= 0) { $title_size = 14; }

        $text_size   = isset($_POST['text_size']) ? intval($_POST['text_size']) : 14;
        if ($text_size <= 0) { $text_size = 14; }

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><scroller_data></scroller_data>');
        $xml->addChild('direction', $direction);
        $xml->addChild('speed', $speed);
        $xml->addChild('bg_color', $bg_color);
        $xml->addChild('title_color', $title_color);
        $xml->addChild('text_color', $text_color);
        $xml->addChild('image_size', $image_size);
        $xml->addChild('title_size', $title_size);
        $xml->addChild('text_size', $text_size);
        
        $items_node = $xml->addChild('news_items');
        if (isset($_POST['news']) && is_array($_POST['news'])) {
            foreach ($_POST['news'] as $item) {
                if (!empty($item['title']) || !empty($item['text'])) {
                    $node = $items_node->addChild('item');
                    $node->addChild('title', htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'));
                    $node->addChild('text', htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8'));
                    $node->addChild('link', htmlspecialchars($item['link'], ENT_QUOTES, 'UTF-8'));
                    $node->addChild('image', htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8'));
                }
            }
        }
        $xml->asXML($data_file);
        echo '<div class="updated" style="display:block;">Settings and news items saved for "' . htmlspecialchars($selected_scroller) . '"!</div>';
    }

    // Refresh scrollers list from disk
    $scrollers = array();
    if (is_dir(GS_NEWS_SCROLLER_DATA_PATH)) {
        $files = glob(GS_NEWS_SCROLLER_DATA_PATH . '*.xml');
        if ($files) {
            foreach ($files as $file) {
                $scrollers[] = basename($file, '.xml');
            }
        }
    }

    if (empty($selected_scroller) && !empty($scrollers)) {
        $selected_scroller = $scrollers[0];
    }
    ?>
    <h3><strong>News Scroller Management</strong></h3>
    <p>Manage multiple news scrollers independently with custom directions, speeds, and styling options.</p>

    <div style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; margin-bottom: 20px;">
        <!-- Select Scroller Form -->
        <form action="load.php" method="get" style="display:inline-block; margin-right: 20px;">
            <input type="hidden" name="id" value="gs_news_scroller">
            <label><strong>Select Scroller:</strong></label>
            <select name="scroller_id" onchange="this.form.submit()" style="padding: 4px;">
                <?php if (empty($scrollers)): ?>
                    <option value="">-- No Scrollers Found --</option>
                <?php else: ?>
                    <?php foreach ($scrollers as $scr): ?>
                        <option value="<?php echo $scr; ?>" <?php if ($selected_scroller === $scr) echo 'selected'; ?>><?php echo $scr; ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </form>

        <!-- Create / Switch Scroller Form -->
        <form action="load.php?id=gs_news_scroller<?php echo !empty($selected_scroller) ? '&scroller_id=' . htmlspecialchars($selected_scroller) : ''; ?>" method="post" style="display:inline-block; margin-right: 5px;">
            <label><strong>New Scroller ID:</strong></label>
            <input type="text" name="new_scroller_id" placeholder="e.g. sidebar-news" class="text" style="width: 130px; display:inline-block;">
            <input type="submit" name="create_scroller" class="submit" value="Create / Switch">
        </form>

        <!-- Delete Scroller Form -->
        <?php if (!empty($selected_scroller)): ?>
            <form action="load.php?id=gs_news_scroller&scroller_id=<?php echo htmlspecialchars($selected_scroller); ?>" method="post" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this scroller?');">
                <input type="submit" name="delete_scroller" class="submit" value="Delete" />
            </form>
        <?php endif; ?>
    </div>

    <?php if (!empty($selected_scroller) && in_array($selected_scroller, $scrollers)): ?>
        <?php 
        $data_file = GS_NEWS_SCROLLER_DATA_PATH . $selected_scroller . '.xml';
        $items = array();
        $saved_direction = 'left';
        $saved_speed = 25;
        $saved_bg_color = '#222222';
        $saved_title_color = '#ffca28';
        $saved_text_color = '#ffffff';
        $saved_image_size = 35;
        $saved_title_size = 14;
        $saved_text_size = 14;

        if (file_exists($data_file)) {
            $xml = @simplexml_load_file($data_file);
            if ($xml) {
                if (isset($xml->direction)) { $saved_direction = (string)$xml->direction; }
                if (isset($xml->speed)) { $saved_speed = (int)$xml->speed; }
                if (isset($xml->bg_color)) { $saved_bg_color = (string)$xml->bg_color; }
                if (isset($xml->title_color)) { $saved_title_color = (string)$xml->title_color; }
                if (isset($xml->text_color)) { $saved_text_color = (string)$xml->text_color; }
                if (isset($xml->image_size)) { $saved_image_size = (int)$xml->image_size; }
                if (isset($xml->title_size)) { $saved_title_size = (int)$xml->title_size; }
                if (isset($xml->text_size)) { $saved_text_size = (int)$xml->text_size; }
                
                $news_node_source = isset($xml->news_items) ? $xml->news_items->item : $xml->item;
                if ($news_node_source) {
                    foreach ($news_node_source as $node) {
                        $items[] = array(
                            'title' => (string)$node->title,
                            'text'  => (string)$node->text,
                            'link'  => (string)$node->link,
                            'image' => (string)$node->image
                        );
                    }
                }
            }
        }
        ?>
        <p style="background: #eef; padding: 10px; border-left: 4px solid #33f;">
            <strong>Template Tag:</strong> 
            <code>&lt;?php echo get_gs_news_scroller('<?php echo $selected_scroller; ?>'); ?&gt;</code><br>
            <strong>Page Shortcode:</strong> 
            <code>(% gs_news_scroller('<?php echo $selected_scroller; ?>') %)</code>
        </p>

        <form action="load.php?id=gs_news_scroller&scroller_id=<?php echo htmlspecialchars($selected_scroller); ?>" method="post">
            
            <div style="background: #fff; padding: 15px; border: 1px solid #ddd; margin-bottom: 20px;">
                <h4><strong>Settings & Styling for: <?php echo htmlspecialchars($selected_scroller); ?></strong></h4>
                
                <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-top: 10px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px;"><strong>Scroll Direction:</strong></label>
                        <select name="scroller_direction" style="padding: 4px;">
                            <option value="left" <?php if ($saved_direction === 'left') echo 'selected'; ?>>Left</option>
                            <option value="right" <?php if ($saved_direction === 'right') echo 'selected'; ?>>Right</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px;"><strong>Scroll Speed (seconds):</strong></label>
                        <input type="number" name="scroller_speed" class="text" value="<?php echo $saved_speed; ?>" min="1" style="width: 80px;" /> 
                        <small>(lower = faster)</small>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px;"><strong>Background Color:</strong></label>
                        <input type="text" name="bg_color" class="text" value="<?php echo htmlspecialchars($saved_bg_color); ?>" style="width: 100px;" placeholder="#222222" />
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px;"><strong>Title Color:</strong></label>
                        <input type="text" name="title_color" class="text" value="<?php echo htmlspecialchars($saved_title_color); ?>" style="width: 100px;" placeholder="#ffca28" />
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px;"><strong>Text Color:</strong></label>
                        <input type="text" name="text_color" class="text" value="<?php echo htmlspecialchars($saved_text_color); ?>" style="width: 100px;" placeholder="#ffffff" />
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px;"><strong>Image Height (px):</strong></label>
                        <input type="number" name="image_size" class="text" value="<?php echo $saved_image_size; ?>" min="1" style="width: 80px;" />
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px;"><strong>Title Size (px):</strong></label>
                        <input type="number" name="title_size" class="text" value="<?php echo $saved_title_size; ?>" min="1" style="width: 80px;" />
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px;"><strong>Text Size (px):</strong></label>
                        <input type="number" name="text_size" class="text" value="<?php echo $saved_text_size; ?>" min="1" style="width: 80px;" />
                    </div>
                </div>
            </div>

            <table id="news_items_table" class="edittable highlight">
                <thead>
                    <tr>
                        <th style="width: 20%;">News Title</th>
                        <th style="width: 30%;">News Text</th>
                        <th style="width: 20%;">Target Link (Optional)</th>
                        <th style="width: 20%;">Image URL (Optional)</th>
                        <th style="width: 1%; text-align: right; padding-left: 5px;">Action</th>
                    </tr>
                </thead>
                <tbody id="news_container">
                    <?php if (empty($items)): ?>
                        <tr class="news-row">
                            <td><input type="text" name="news[0][title]" class="text" placeholder="Title" style="width:100%;"></td>
                            <td><input type="text" name="news[0][text]" class="text" placeholder="News content..." style="width:100%;"></td>
                            <td><input type="text" name="news[0][link]" class="text" placeholder="https://..." style="width:100%;"></td>
                            <td><input type="text" name="news[0][image]" class="text" placeholder="URL or path" style="width:100%;"></td>
                            <td style="text-align: left; padding-left: 5px;"><a href="#" class="cancel remove-row" title="Delete" style="color: #b7b7b7 !important; text-decoration: none; font-size:18px;">&times;</a></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $i => $item): ?>
                            <tr class="news-row">
                                <td><input type="text" name="news[<?php echo $i; ?>][title]" class="text" value="<?php echo htmlspecialchars($item['title']); ?>" style="width:100%;"></td>
                                <td><input type="text" name="news[<?php echo $i; ?>][text]" class="text" value="<?php echo htmlspecialchars($item['text']); ?>" style="width:100%;"></td>
                                <td><input type="text" name="news[<?php echo $i; ?>][link]" class="text" value="<?php echo htmlspecialchars($item['link']); ?>" style="width:100%;"></td>
                                <td><input type="text" name="news[<?php echo $i; ?>][image]" class="text" value="<?php echo htmlspecialchars($item['image']); ?>" style="width:100%;"></td>
                                <td style="text-align: right; padding-left: 5px;"><a href="#" class="cancel remove-row" title="Delete" style="color: #b7b7b7 !important; text-decoration: none; font-size:18px;">&times;</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <p style="margin-top: 15px;">
                <a href="#" id="add_more_news" class="button">+ Add News Item</a>
            </p>

            <p class="submit" style="margin-top: 20px;">
                <input type="submit" name="save_news" class="submit" value="Save Scroller" />
            </p>
        </form>

        <script>
        jQuery(document).ready(function($) {
            let rowIdx = $('#news_container tr').length;

            $('#add_more_news').on('click', function(e) {
                e.preventDefault();
                let newRow = '<tr class="news-row">' +
                    '<td><input type="text" name="news[' + rowIdx + '][title]" class="text" placeholder="Title" style="width:100%;"></td>' +
                    '<td><input type="text" name="news[' + rowIdx + '][text]" class="text" placeholder="News content..." style="width:100%;"></td>' +
                    '<td><input type="text" name="news[' + rowIdx + '][link]" class="text" placeholder="https://..." style="width:100%;"></td>' +
                    '<td><input type="text" name="news[' + rowIdx + '][image]" class="text" placeholder="URL or path" style="width:100%;"></td>' +
                    '<td style="text-align: right; padding-left: 5px;"><a href="#" class="cancel remove-row" title="Delete" style="color: #b7b7b7 !important; text-decoration: none; font-size:18px;">&times;</a></td>' +
                    '</tr>';
                $('#news_container').append(newRow);
                rowIdx++;
            });

            $(document).on('click', '.remove-row', function(e) {
                e.preventDefault();
                $(this).closest('tr').remove();
            });
        });
        </script>
    <?php endif; ?>
    <?php
}

function get_gs_news_scroller($scroller_id = 'default') {
    $scroller_id = preg_replace('/[^a-zA-Z0-9_\-]/', '', $scroller_id);
    
    if (!file_exists(GS_NEWS_SCROLLER_DATA_PATH)) {
        @mkdir(GS_NEWS_SCROLLER_DATA_PATH, 0755, true);
    }

    $data_file = GS_NEWS_SCROLLER_DATA_PATH . $scroller_id . '.xml';

    if (!file_exists($data_file)) {
        return '<p>No news items found for scroller ID: ' . htmlspecialchars($scroller_id) . '</p>';
    }

    $xml = @simplexml_load_file($data_file);
    if (!$xml) {
        return '<p>No news items found.</p>';
    }

    $direction   = isset($xml->direction) ? (string)$xml->direction : 'left';
    $speed_val   = isset($xml->speed) ? (int)$xml->speed : 25;
    $speed       = $speed_val . 's';
    
    $bg_color    = isset($xml->bg_color) ? (string)$xml->bg_color : '#222222';
    $title_color = isset($xml->title_color) ? (string)$xml->title_color : '#ffca28';
    $text_color  = isset($xml->text_color) ? (string)$xml->text_color : '#ffffff';
    $image_size  = isset($xml->image_size) ? (int)$xml->image_size : 35;
    $title_size  = isset($xml->title_size) ? (int)$xml->title_size : 14;
    $text_size   = isset($xml->text_size) ? (int)$xml->text_size : 14;

    $news_node_source = isset($xml->news_items) ? $xml->news_items->item : $xml->item;
    if (!$news_node_source || count($news_node_source) === 0) {
        return '<p>No news items found.</p>';
    }

    $kf_start = ($direction === 'right') ? '-50%' : '0%';
    $kf_end   = ($direction === 'right') ? '0%' : '-50%';
    $unique_anim_name = 'gsNewsScroll_' . $scroller_id;

    $html = '
    <style>
        .gs-news-scroller-container-' . htmlspecialchars($scroller_id, ENT_QUOTES, 'UTF-8') . ' {
            overflow: hidden;
            width: 100%;
            white-space: nowrap;
            position: relative;
            background: ' . htmlspecialchars($bg_color, ENT_QUOTES, 'UTF-8') . ';
            color: ' . htmlspecialchars($text_color, ENT_QUOTES, 'UTF-8') . ';
            padding: 10px 0;
            box-sizing: border-box;
        }
        .gs-news-scroller-container-' . htmlspecialchars($scroller_id, ENT_QUOTES, 'UTF-8') . ' .gs-news-scroller-track {
            display: inline-flex;
            gap: 40px;
            width: max-content;
            animation: ' . $unique_anim_name . ' ' . htmlspecialchars($speed, ENT_QUOTES, 'UTF-8') . ' linear infinite;
        }
        .gs-news-scroller-container-' . htmlspecialchars($scroller_id, ENT_QUOTES, 'UTF-8') . ':hover .gs-news-scroller-track {
            animation-play-state: paused;
        }
        .gs-news-scroller-container-' . htmlspecialchars($scroller_id, ENT_QUOTES, 'UTF-8') . ' .gs-news-item {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            color: ' . htmlspecialchars($text_color, ENT_QUOTES, 'UTF-8') . ';
            text-decoration: none;
            font-family: sans-serif;
            font-size: ' . $text_size . 'px;
        }
        .gs-news-scroller-container-' . htmlspecialchars($scroller_id, ENT_QUOTES, 'UTF-8') . ' .gs-news-item img {
            height: ' . $image_size . 'px;
            width: auto;
            object-fit: cover;
        }
        .gs-news-scroller-container-' . htmlspecialchars($scroller_id, ENT_QUOTES, 'UTF-8') . ' .gs-news-title {
            font-weight: bold;
            color: ' . htmlspecialchars($title_color, ENT_QUOTES, 'UTF-8') . ';
            font-size: ' . $title_size . 'px;
        }
        .gs-news-scroller-container-' . htmlspecialchars($scroller_id, ENT_QUOTES, 'UTF-8') . ' .gs-news-text {
            font-size: ' . $text_size . 'px;
        }
        @keyframes ' . $unique_anim_name . ' {
            0% { transform: translateX(' . $kf_start . '); }
            100% { transform: translateX(' . $kf_end . '); }
        }
    </style>';

    $items_html = '';
    foreach ($news_node_source as $item) {
        $title = html_entity_decode((string)$item->title, ENT_QUOTES, 'UTF-8');
        $text  = html_entity_decode((string)$item->text, ENT_QUOTES, 'UTF-8');
        $link  = html_entity_decode((string)$item->link, ENT_QUOTES, 'UTF-8');
        $image = html_entity_decode((string)$item->image, ENT_QUOTES, 'UTF-8');

        $tag = !empty($link) ? 'a' : 'div';
        $href = !empty($link) ? ' href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '" target="_blank"' : '';

        $items_html .= '<' . $tag . ' class="gs-news-item"' . $href . '>';
        
        if (!empty($image)) {
            $items_html .= '<img src="' . htmlspecialchars($image, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">';
        }
        if (!empty($title)) {
            $items_html .= '<span class="gs-news-title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . ':</span>';
        }
        if (!empty($text)) {
            $items_html .= '<span class="gs-news-text">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</span>';
        }
        
        $items_html .= '</' . $tag . '>';
    }

    $html .= '
    <div class="gs-news-scroller-container-' . htmlspecialchars($scroller_id, ENT_QUOTES, 'UTF-8') . '">
        <div class="gs-news-scroller-track">
            ' . $items_html . $items_html . '
        </div>
    </div>';

    return $html;
}

/**
 * Shortcode filter handler for GetSimple CMS pages
 */
function gs_news_scroller_shortcode_filter($content) {
    $pattern = '/\(%\s*gs_news_scroller\s*\(\s*[\'"]?([a-zA-Z0-9_\-]*)[\'"]?\s*\)\s*%\)/i';
    return preg_replace_callback($pattern, function($matches) {
        $scroller_id = isset($matches[1]) && !empty($matches[1]) ? $matches[1] : 'default';
        return get_gs_news_scroller($scroller_id);
    }, $content);
}