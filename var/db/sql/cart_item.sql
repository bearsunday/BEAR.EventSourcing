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
WHERE id = :id
LIMIT 1
