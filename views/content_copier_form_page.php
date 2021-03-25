<form method="post">
    <h1>Multisite content copier</h1>
    <?php if ( ! empty( $sites ) ) : ?>
        <h3>This plugin DOES NOT create a database backup. You must do this yourself before submitting the form
            below.</h3>
        <p>Choose a site to copy the content FROM:</p>
        <?php foreach ( $sites as $site_id => $site_details ) : ?>
            <label><input type="radio" name="site_from"
                          value="<?php echo $site_id; ?>"><?php echo $site_details['name']; ?>
                (ID: <?php echo $site_id; ?>)</label><br>
        <?php endforeach; ?>
        <p>Choose a site to copy the site content TO:</p>
        <?php foreach ( $sub_sites as $site_id => $site_details ) : ?>
            <label><input type="checkbox" name="sites_to[<?php echo $site_id; ?>]"
                          value="<?php echo $site_details['name']; ?>"><?php echo $site_details['name']; ?>
                (ID: <?php echo $site_id; ?>)</label><br>
        <?php endforeach; ?>
        <input type="hidden" name="rk_wp_copy_content" value="1">
        <?php submit_button( 'Copy content' ); ?>
    <?php else : ?>
        <p>Looks like there aren't any sub-sites installed on the network.</p>
    <?php endif; ?>
</form>
