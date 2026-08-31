-- قاسم اختياري للعمود المحسوب، ليدعم رؤوسًا مثل «المجموع 8/2» أي مجموع المكوّنات مقسومًا على 2.
-- القيمة الافتراضية 1 فلا يتغير أي قالب قائم.
ALTER TABLE table_formulas
    ADD COLUMN divisor DECIMAL(10,4) NOT NULL DEFAULT 1 AFTER percentage_base;

ALTER TABLE table_formulas
    ADD CONSTRAINT chk_formula_divisor CHECK (divisor > 0);
