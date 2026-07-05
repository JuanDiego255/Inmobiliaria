-- ============================================================
-- SCRIPT: Registrar migraciones existentes en la BD central
-- ============================================================
-- Ejecutá este script en la BD central (safewor_rs) ANTES de
-- correr php artisan migrate. Esto registra todas las migraciones
-- cuyas tablas ya existen, evitando que artisan intente recrearlas.
--
-- Uso: mysql -u root -p safewor_rs < database/register_existing_migrations.sql
-- O copiá y pegá en phpMyAdmin / MySQL Workbench / HeidiSQL
-- ============================================================

-- Paso 1: Verificar que la tabla migrations existe
CREATE TABLE IF NOT EXISTS `migrations` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `migration` varchar(255) NOT NULL,
    `batch` int NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Paso 2: Insertar SOLO las que NO estén registradas
-- (si ya están, no las duplica)

INSERT INTO `migrations` (`migration`, `batch`)
SELECT t.migration, 1 FROM (
    -- === ROOT / Laravel ===
    SELECT '2014_10_12_000000_create_users_table' AS migration
    UNION ALL SELECT '2014_10_12_100000_create_password_reset_tokens_table'
    UNION ALL SELECT '2019_01_05_053554_create_jobs_table'
    UNION ALL SELECT '2019_08_19_000000_create_failed_jobs_table'
    UNION ALL SELECT '2019_12_14_000001_create_personal_access_tokens_table'
    UNION ALL SELECT '2021_08_05_134214_fix_social_link_theme_options'
    UNION ALL SELECT '2022_06_04_033634_improve_homepage_content'
    UNION ALL SELECT '2022_11_06_070405_improve_homepage_search_box'
    UNION ALL SELECT '2023_05_08_114004_improve_properties_and_projects_page'
    UNION ALL SELECT '2023_07_18_040500_convert_cities_is_featured_to_selecting_locations_from_shortcode'

    -- === TENANCY (tenants + domains) ===
    UNION ALL SELECT '2026_07_02_000001_create_tenants_table'
    UNION ALL SELECT '2026_07_02_000002_create_domains_table'

    -- === CORE: ACL ===
    UNION ALL SELECT '2016_06_10_230148_create_acl_tables'
    UNION ALL SELECT '2023_02_16_042611_drop_table_password_resets'
    UNION ALL SELECT '2023_05_10_075124_drop_column_id_in_role_users_table'

    -- === CORE: Base ===
    UNION ALL SELECT '2013_04_09_032329_create_base_tables'
    UNION ALL SELECT '2022_07_10_034813_move_lang_folder_to_root'
    UNION ALL SELECT '2022_09_01_000001_create_admin_notifications_tables'
    UNION ALL SELECT '2023_04_23_005903_add_column_permissions_to_admin_notifications'

    -- === CORE: Dashboard ===
    UNION ALL SELECT '2016_11_28_032840_create_dashboard_widget_tables'

    -- === CORE: Media ===
    UNION ALL SELECT '2017_05_09_070343_create_media_tables'
    UNION ALL SELECT '2022_04_20_100851_add_index_to_media_table'
    UNION ALL SELECT '2023_01_30_024431_add_alt_to_media_table'

    -- === CORE: Setting ===
    UNION ALL SELECT '2016_10_05_074239_create_setting_table'
    UNION ALL SELECT '2022_11_18_063357_add_missing_timestamp_in_table_settings'

    -- === PACKAGES: Menu ===
    UNION ALL SELECT '2016_06_14_230857_create_menus_table'
    UNION ALL SELECT '2022_04_20_101046_add_index_to_menu_table'

    -- === PACKAGES: Page ===
    UNION ALL SELECT '2016_06_28_221418_create_pages_table'
    UNION ALL SELECT '2022_10_14_024629_drop_column_is_featured'
    UNION ALL SELECT '2023_08_21_090810_make_page_content_nullable'

    -- === PACKAGES: Revision ===
    UNION ALL SELECT '2013_04_09_062329_create_revisions_table'

    -- === PACKAGES: Slug ===
    UNION ALL SELECT '2017_11_03_070450_create_slug_table'
    UNION ALL SELECT '2022_12_02_093615_update_slug_index_columns'
    UNION ALL SELECT '2023_09_14_021936_update_index_for_slugs_table'

    -- === PACKAGES: Widget ===
    UNION ALL SELECT '2016_12_16_084601_create_widgets_table'

    -- === PLUGINS: Audit Log ===
    UNION ALL SELECT '2015_06_29_025744_create_audit_history'
    UNION ALL SELECT '2023_11_14_033417_change_request_column_in_table_audit_histories'

    -- === PLUGINS: Blog ===
    UNION ALL SELECT '2015_06_18_033822_create_blog_table'
    UNION ALL SELECT '2021_02_16_092633_remove_default_value_for_author_type'
    UNION ALL SELECT '2021_12_03_030600_create_blog_translations'
    UNION ALL SELECT '2022_04_19_113923_add_index_to_table_posts'
    UNION ALL SELECT '2023_08_29_074620_make_column_author_id_nullable'

    -- === PLUGINS: Career ===
    UNION ALL SELECT '2019_06_24_211801_create_career_table'
    UNION ALL SELECT '2021_12_04_095357_create_careers_translations_table'
    UNION ALL SELECT '2023_09_20_050420_add_missing_translation_column'

    -- === PLUGINS: Contact ===
    UNION ALL SELECT '2016_06_17_091537_create_contacts_table'

    -- === PLUGINS: Language ===
    UNION ALL SELECT '2016_10_03_032336_create_languages_table'
    UNION ALL SELECT '2023_09_14_022423_add_index_for_language_table'

    -- === PLUGINS: Language Advanced ===
    UNION ALL SELECT '2021_10_25_021023_fix-priority-load-for-language-advanced'
    UNION ALL SELECT '2021_12_03_075608_create_page_translations'
    UNION ALL SELECT '2023_07_06_011444_create_slug_translations_table'

    -- === PLUGINS: Location ===
    UNION ALL SELECT '2019_11_18_061011_create_country_table'
    UNION ALL SELECT '2021_12_03_084118_create_location_translations'
    UNION ALL SELECT '2021_12_03_094518_migrate_old_location_data'
    UNION ALL SELECT '2021_12_10_034440_switch_plugin_location_to_use_language_advanced'
    UNION ALL SELECT '2022_01_16_085908_improve_plugin_location'
    UNION ALL SELECT '2022_08_04_052122_delete_location_backup_tables'
    UNION ALL SELECT '2023_04_23_061847_increase_state_translations_abbreviation_column'
    UNION ALL SELECT '2023_07_26_041451_add_more_columns_to_location_table'
    UNION ALL SELECT '2023_07_27_041451_add_more_columns_to_location_translation_table'
    UNION ALL SELECT '2023_08_15_073307_drop_unique_in_states_cities_translations'
    UNION ALL SELECT '2023_10_21_065016_make_state_id_in_table_cities_nullable'

    -- === PLUGINS: Payment ===
    UNION ALL SELECT '2017_05_18_080441_create_payment_tables'
    UNION ALL SELECT '2021_03_27_144913_add_customer_type_into_table_payments'
    UNION ALL SELECT '2021_05_24_034720_make_column_currency_nullable'
    UNION ALL SELECT '2021_08_09_161302_add_metadata_column_to_payments_table'
    UNION ALL SELECT '2021_10_19_020859_update_metadata_field'
    UNION ALL SELECT '2022_06_28_151901_activate_paypal_stripe_plugin'
    UNION ALL SELECT '2022_07_07_153354_update_charge_id_in_table_payments'

    -- === PLUGINS: Real Estate (todas las existentes) ===
    UNION ALL SELECT '2018_06_22_032304_create_real_estate_table'
    UNION ALL SELECT '2021_02_11_031126_update_price_column_in_projects_and_properties'
    UNION ALL SELECT '2021_03_08_024049_add_lat_long_into_real_estate_tables'
    UNION ALL SELECT '2021_06_10_091950_add_column_is_featured_to_table_re_accounts'
    UNION ALL SELECT '2021_07_07_021757_update_table_account_activity_logs'
    UNION ALL SELECT '2021_09_29_042758_create_re_categories_multilevel_table'
    UNION ALL SELECT '2021_10_31_031254_add_company_to_accounts_table'
    UNION ALL SELECT '2021_12_10_034807_create_real_estate_translation_tables'
    UNION ALL SELECT '2021_12_18_081636_add_property_project_views_count'
    UNION ALL SELECT '2022_05_04_033044_update_column_images_in_real_estate_tables'
    UNION ALL SELECT '2022_07_02_081723_fix_expired_date_column'
    UNION ALL SELECT '2022_08_01_090213_update_table_properties_and_projects'
    UNION ALL SELECT '2023_01_31_023233_create_re_custom_fields_table'
    UNION ALL SELECT '2023_02_06_000000_add_location_to_re_accounts_table'
    UNION ALL SELECT '2023_02_06_024257_add_package_translations'
    UNION ALL SELECT '2023_02_08_062457_add_custom_fields_translation_table'
    UNION ALL SELECT '2023_02_15_024644_create_re_reviews_table'
    UNION ALL SELECT '2023_02_20_072604_create_re_invoices_table'
    UNION ALL SELECT '2023_02_20_081251_create_re_account_packages_table'
    UNION ALL SELECT '2023_04_04_030709_add_unique_id_to_properties_and_projects_table'
    UNION ALL SELECT '2023_04_14_164811_make_phone_and_email_in_table_re_consults_nullable'
    UNION ALL SELECT '2023_05_09_062031_unique_reviews_table'
    UNION ALL SELECT '2023_05_26_034353_fix_properties_projects_image'
    UNION ALL SELECT '2023_05_27_004215_add_column_ip_into_table_re_consults'
    UNION ALL SELECT '2023_07_25_034513_create_re_coupons_table'
    UNION ALL SELECT '2023_07_25_034672_add_coupon_code_column_to_jb_invoices_table'
    UNION ALL SELECT '2023_08_02_074208_change_square_column_to_float'
    UNION ALL SELECT '2023_08_07_000001_add_is_public_profile_column_to_re_accounts_table'
    UNION ALL SELECT '2023_08_09_004607_make_column_project_id_nullable'
    UNION ALL SELECT '2023_09_11_084630_update_mandatory_fields_in_consult_form_table'
    UNION ALL SELECT '2024_01_15_000001_create_re_clients_and_boards_tables'
    UNION ALL SELECT '2024_01_16_000001_add_property_status_to_re_board_properties'

    -- === PLUGINS: Real Estate - CRM (creadas por nosotros, tablas ya existen) ===
    UNION ALL SELECT '2026_06_18_000001_create_re_crm_tables'
    UNION ALL SELECT '2026_06_18_000002_create_re_crm_tasks_reminders_tables'
    UNION ALL SELECT '2026_06_22_000001_create_meta_webhook_integration'
    UNION ALL SELECT '2026_06_25_000001_create_whatsapp_conversations_table'
    UNION ALL SELECT '2026_06_29_000001_add_virtual_tour_url_to_properties_and_projects'
    UNION ALL SELECT '2026_07_02_000001_add_tenant_id_to_whatsapp_conversations'
    UNION ALL SELECT '2026_07_04_000001_create_whatsapp_bot_flows_and_mail_config_tables'

    -- === PLUGINS: Translation ===
    UNION ALL SELECT '2016_10_07_193005_create_translations_table'
) t
WHERE t.migration NOT IN (SELECT migration FROM migrations);

-- ============================================================
-- Verificación: mostrar lo que quedó registrado
-- ============================================================
SELECT COUNT(*) AS total_registered FROM migrations;
SELECT migration, batch FROM migrations ORDER BY migration;
