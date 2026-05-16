SELECT
    id,
    order_no,
    pre_order_id,
    message,
    name01,
    name02,
    kana01,
    kana02,
    company_name,
    email,
    phone_number,
    postal_code,
    addr01,
    addr02,
    subtotal,
    discount,
    delivery_fee_total,
    charge,
    tax,
    total,
    payment_total,
    currency_code,
    create_date,
    update_date,
    order_date,
    payment_date,
    customer_id,
    order_status_id AS status_id
FROM dtb_order
WHERE (:customerId IS NULL OR customer_id = :customerId)
  AND (:statusId IS NULL OR order_status_id = :statusId)
  AND (:orderNo IS NULL OR order_no = :orderNo)
ORDER BY create_date DESC, id DESC
