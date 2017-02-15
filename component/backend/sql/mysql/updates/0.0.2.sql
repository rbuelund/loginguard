-- Add a Created On column and give it a sane default value
ALTER TABLE `#__loginguard_tfa`
  ADD COLUMN `created_on` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';

UPDATE `#__loginguard_tfa` SET `created_on` = NOW();

-- Add a Last Used, UA and IP columns (when and who last used this TSV method)
ALTER TABLE `#__loginguard_tfa`
  ADD COLUMN `last_used` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  ADD COLUMN `ua` VARCHAR(190) NULL,
  ADD COLUMN `ip` VARCHAR(190) NULL;