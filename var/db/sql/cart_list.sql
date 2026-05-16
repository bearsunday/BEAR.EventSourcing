SELECT
    id,
    cart_key,
    pre_order_id,
    total_price,
    delivery_fee_total,
    sort_no,
    create_date,
    update_date,
    customer_id
FROM dtb_cart
WHERE (:customerId IS NULL OR customer_id = :customerId)
ORDER BY update_date DESC, id DESC
