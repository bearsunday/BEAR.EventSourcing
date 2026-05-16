SELECT
    id,
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
    birth,
    buy_times,
    buy_total,
    point,
    create_date,
    update_date,
    customer_status_id AS status_id
FROM dtb_customer
WHERE id = :id
LIMIT 1
