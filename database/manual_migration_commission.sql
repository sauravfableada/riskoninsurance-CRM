-- ============================================================
-- Manual Migration for Fablead CRM Production Database
-- Tables: company_rules, commission_rules, commission_entries
-- Run this in phpMyAdmin > SQL tab on your production database
-- ============================================================

-- Step 1: Create company_rules table (dependency of commission_rules)
CREATE TABLE IF NOT EXISTS `company_rules` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 2: Create commission_rules table
CREATE TABLE IF NOT EXISTS `commission_rules` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `company_rule_id` BIGINT UNSIGNED NULL,
    `company_id` BIGINT UNSIGNED NOT NULL,
    `policy_type_id` BIGINT UNSIGNED NULL,
    `agent_id` BIGINT UNSIGNED NULL,
    `rule_name` VARCHAR(255) NOT NULL,
    `commission_type` ENUM('percentage', 'flat') NOT NULL DEFAULT 'percentage',
    `commission_value` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `min_premium` DECIMAL(15,2) NULL,
    `max_premium` DECIMAL(15,2) NULL,
    `effective_from` DATE NULL,
    `effective_to` DATE NULL,
    `description` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    CONSTRAINT `commission_rules_company_rule_id_foreign`
        FOREIGN KEY (`company_rule_id`) REFERENCES `company_rules`(`id`) ON DELETE SET NULL,
    CONSTRAINT `commission_rules_company_id_foreign`
        FOREIGN KEY (`company_id`) REFERENCES `insurance_companies`(`id`) ON DELETE CASCADE,
    CONSTRAINT `commission_rules_policy_type_id_foreign`
        FOREIGN KEY (`policy_type_id`) REFERENCES `insurance_policy_types`(`id`) ON DELETE SET NULL,
    CONSTRAINT `commission_rules_agent_id_foreign`
        FOREIGN KEY (`agent_id`) REFERENCES `agents`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 3: Create commission_entries table
CREATE TABLE IF NOT EXISTS `commission_entries` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `commission_rule_id` BIGINT UNSIGNED NOT NULL,
    `policy_id` BIGINT UNSIGNED NULL,
    `agent_id` BIGINT UNSIGNED NULL,
    `premium_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `commission_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `commission_percentage` DECIMAL(8,2) NULL,
    `payment_status` ENUM('pending', 'paid', 'cancelled') NOT NULL DEFAULT 'pending',
    `remarks` TEXT NULL,
    `entry_date` DATE NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    CONSTRAINT `commission_entries_commission_rule_id_foreign`
        FOREIGN KEY (`commission_rule_id`) REFERENCES `commission_rules`(`id`) ON DELETE CASCADE,
    CONSTRAINT `commission_entries_policy_id_foreign`
        FOREIGN KEY (`policy_id`) REFERENCES `policies`(`id`) ON DELETE SET NULL,
    CONSTRAINT `commission_entries_agent_id_foreign`
        FOREIGN KEY (`agent_id`) REFERENCES `agents`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 4: Record these in Laravel's migrations table so artisan migrate does not re-run them
INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES
    ('2026_05_04_000001_create_company_rules_table', 99),
    ('2026_05_04_000002_create_commission_rules_table', 99),
    ('2026_05_04_000003_create_commission_entries_table', 99);
