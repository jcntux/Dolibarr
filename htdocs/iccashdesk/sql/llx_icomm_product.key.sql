ALTER TABLE `llx_icomm_product` ADD UNIQUE INDEX `idx_icomm_product` (`fk_product`, `fk_user`, `fk_type`, `date`);
