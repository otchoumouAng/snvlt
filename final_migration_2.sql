ALTER TABLE metier.pay_trans_transactions ADD tresorpay_receipt_reference VARCHAR(255) DEFAULT NULL;
ALTER TABLE metier.pay_trans_transactions ADD paid_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL;
ALTER TABLE metier.pay_trans_transactions ADD payer_phone VARCHAR(255) DEFAULT NULL;
ALTER TABLE metier.pay_trans_transactions ADD paid_amount NUMERIC(10, 0) DEFAULT NULL;
