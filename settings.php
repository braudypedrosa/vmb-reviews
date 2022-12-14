<?php ob_start();

include_once(VMB_REVIEWS_DIR.'functions.php');

$err = "";
$last_page = get_option('page');
$total_pages = get_option('total_pages');


if(isset($_POST['save_and_fetch'])){
    
    _save();

    echo REQUEST_URL;

    // start fetch
    _vmbreviews_get_all_reviews(get_option('page'), 0);

    wp_redirect(admin_url("edit.php?post_type=vmb_reviews&page=settings&status=settings_saved_fetch"));
    exit();
}

if(isset($_POST['save_settings'])){
    
    _save();

    wp_redirect(admin_url("edit.php?post_type=vmb_reviews&page=settings&status=settings_saved"));
    exit();
}

if(isset($_POST['renew_reviews'])) {
    _renew_reviews();
    wp_redirect(admin_url("edit.php?post_type=vmb_reviews&page=settings&status=reviews_renewed"));
}


?>
<style>
    .vmbreviews-form-input label {
        display: block;
        font-weight: bold;
        margin-top: 15px;
        margin-bottom: 5px;
    }

    .vmbreviews-form-input.submit-btn {
        margin-top: 30px;
        margin-right: 15px;
    }

    .vmbreviews-button-group {
        display: flex;
    }

    .loader {
        position: fixed;
        z-index: 9999999;
        left: 0;
        right: 0;
        top: 0;
        bottom: 0;
        background: #0000006b;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }

    .loader h3 {
        color: white;
    }

    .loader img {
        width: 80px;
    }
</style>

<div class="wrap">
    <h2>Review Settings</h2>
    <?php if(isset($_GET['status']) && $_GET['status'] == "settings_saved"): ?>
        <div class="notice notice-success is-dismissible">
            <p>Settings saved successfuly!</p>
        </div>
    <?php elseif(isset($_GET['status']) && $_GET['status'] == "settings_saved_fetch"): ?>
        <div class="notice notice-success is-dismissible">
            <p>Fetched new reviews and saved settings successfully!</p>
        </div>
    <?php elseif(isset($_GET['status']) && $_GET['status'] == "reviews_renewed"): ?>
        <div class="notice notice-success is-dismissible">
            <p>Success! All reviews that are 3 months and older were set to "Draft"!</p>
        </div>
    <?php endif;?>
    <?php echo !empty($err) ? $err : ""; ?>

    <div class="vmbreviews-settings-content">
        
        <form method="post">
            <h3>API Information</h3>
            <p>Click <a target="blank" href="<?php echo VMB_REVIEWS_URL.'cheatsheet.txt'; ?>">here</a> to view Site ID cheatsheet.</p>
            
            <div class="vmbreviews-form-input">
                <label>Site ID</label>
                <input type="text" name="site_id" required value="<?php echo isset($_POST['site_id']) ? $_POST['site_id'] : get_option('site_id'); ?>"/>
            </div>
            <div class="vmbreviews-form-input">
                <label>API Token</label>
                <input type="password" name="api_token" required value="<?php echo isset($_POST['api_token']) ? $_POST['api_token'] : get_option('api_token'); ?>"/>
            </div>
            <div class="vmbreviews-form-input">
                <label>API Secret</label>
                <input type="password" name="api_secret" required value="<?php echo isset($_POST['api_secret']) ? $_POST['api_secret'] : get_option('api_secret'); ?>"/>
            </div>

            <h3 style="margin-top: 30px;">Reviews Settings</h3>
            <div class="vmbreviews-form-input">
                <label>Reviews to fetch</label>
                <span style="display: block!important; font-size: 12px; font-style: italic; margin-bottom: 5px;"><b>Note:</b> Fetching many reviews will cause long loading time. Recommended value is <b>25.</b></span>
                <input type="number" name="reviews_to_fetch" min="1" max="30" required value="<?php echo isset($_POST['reviews_to_fetch']) ? $_POST['reviews_to_fetch'] : get_option('reviews_to_fetch'); ?>"/>
            </div>

            <div class="vmbreviews-form-input">
                <label>Minimum rating to fetch</label>
                <span style="display: block!important; font-size: 12px; font-style: italic; margin-bottom: 5px;"><b>Note:</b> Fetching high rating reviews will cause long loading time. Recommended value is <b>3.</b></span>
                <input type="number" name="rating_to_fetch" required min="1" max="5" value="<?php echo isset($_POST['rating_to_fetch']) ? $_POST['rating_to_fetch'] : get_option('rating_to_fetch'); ?>"/>
            </div>

            <div class="vmbreviews-form-input">
                <label>Auto-refresh and renew interval in hours:</label>
                <input type="number" name="ref_ren_interval" required min="1" max="24" value="<?php echo isset($_POST['ref_ren_interval']) ? $_POST['ref_ren_interval'] : get_option('ref_ren_interval'); ?>"/>
            </div>

            <h3 style="margin-top: 30px;">Shortcode Settings</h3>
            <div class="vmbreviews-form-input">
                <label>Reviews to display</label>
                <input type="number" name="reviews_to_display" required min="1" max="40" value="<?php echo isset($_POST['reviews_to_display']) ? $_POST['reviews_to_display'] : get_option('reviews_to_display'); ?>"/>
            </div>

            <div class="vmbreviews-form-input">
                <label>Number of columns</label>
                <input type="number" name="column_count" required min="1" max="4" value="<?php echo isset($_POST['column_count']) ? $_POST['column_count'] : get_option('column_count'); ?>"/>
            </div>

            <div class="vmbreviews-button-group">
                <div class="vmbreviews-form-input submit-btn">
                    <input type="submit" name="save_and_fetch" class="button-primary" onClick="showLoader()" value="Save and Fetch"/>    
                </div>

                <div class="vmbreviews-form-input submit-btn">
                    <input type="submit" name="save_settings" class="button-primary" value="Save Settings"/>    
                </div>

                <div class="vmbreviews-form-input submit-btn">
                    <input type="submit" name="renew_reviews" class="button-primary" value="Renew Reviews"/>    
                </div>
            </div>

            <!-- <p>Last Page: <?php echo $last_page; ?></p>
            <p>Total Page: <?php echo $total_pages; ?></p> -->

            <h4 style="margin-top: 30px;">
                Available shortcodes:
                <span style="font-weight:bold; display: block;">[display_reviews] - displays all reviews according to the settings above.</span>
            </h4>

        </form>

    </div>

    <div class="loader" style="display:none;">
        <img src="<?php echo VMB_REVIEWS_URL.'assets/loading-gear-white.svg'; ?>" alt="loader"/>
        <h3>Fetching reviews...</h3>
    </div>

    <script>
        function showLoader(){
            jQuery('.loader').toggle();
        }
    </script>

</div>
