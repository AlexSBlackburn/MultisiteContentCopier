<?php
/**
 * @throws \MultisiteContentCopier\Exceptions\DependencyNotFoundException
 */
function multisite_content_copier_copy_table() {
    die( \MultisiteContentCopier\Controllers\ContentCopierController::copy_table() );
}
add_action( 'wp_ajax_multisite_content_copier_copy_table', 'multisite_content_copier_copy_table' );
add_action( 'wp_ajax_nopriv_multisite_content_copier_copy_table', 'multisite_content_copier_copy_table' );

/**
 * @throws \MultisiteContentCopier\Exceptions\DependencyNotFoundException
 */
function multisite_content_copier_delete_files() {
    die( \MultisiteContentCopier\Controllers\ContentCopierController::delete_uploads() );
}
add_action( 'wp_ajax_multisite_content_copier_delete_files', 'multisite_content_copier_delete_files' );
add_action( 'wp_ajax_nopriv_multisite_content_copier_delete_files', 'multisite_content_copier_delete_files' );

/**
 * @throws \MultisiteContentCopier\Exceptions\DependencyNotFoundException
 */
function multisite_content_copier_copy_files() {
    die( \MultisiteContentCopier\Controllers\ContentCopierController::copy_uploads() );
}
add_action( 'wp_ajax_multisite_content_copier_copy_files', 'multisite_content_copier_copy_files' );
add_action( 'wp_ajax_nopriv_multisite_content_copier_copy_files', 'multisite_content_copier_copy_files' );

