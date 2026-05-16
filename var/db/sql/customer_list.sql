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
WHERE (:name IS NULL OR name01 LIKE CONCAT('%', :name, '%') OR name02 LIKE CONCAT('%', :name, '%'))
  AND (:email IS NULL OR email LIKE CONCAT('%', :email, '%'))
  AND (:statusId IS NULL OR customer_status_id = :statusId)
ORDER BY update_date DESC, id DESC
