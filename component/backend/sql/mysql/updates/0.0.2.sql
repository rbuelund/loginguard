-- Add a Created On column and give it a sane default value
ALTER TABLE `#__loginguard_tfa`
  ADD COLUMN `created_on` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `options`;

UPDATE `#__loginguard_tfa` SET `created_on` = NOW();

-- Add a Last Used column and give it a sane default value
ALTER TABLE `#__loginguard_tfa`
  ADD COLUMN `last_used` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `created_on`;
