SELECT
    c.id,
    c.email,
    c.name01,
    c.name02,
    c.kana01,
    c.kana02,
    c.company_name,
    c.postal_code,
    c.addr01,
    c.addr02,
    c.phone_number,
    c.birth,
    c.point,
    c.buy_times,
    c.buy_total,
    c.first_buy_date,
    c.last_buy_date,
    c.create_date,
    c.update_date,
    cs.id AS status_id,
    cs.name AS status_name,
    p.id AS pref_id,
    p.name AS pref_name
FROM customer c
LEFT JOIN mtb_customer_status cs ON c.customer_status_id = cs.id
LEFT JOIN mtb_pref p ON c.pref_id = p.id
WHERE 1=1
/* BEGIN email */
AND c.email LIKE :email
/* END email */
/* BEGIN name */
AND (c.name01 LIKE :name OR c.name02 LIKE :name)
/* END name */
/* BEGIN status */
AND c.customer_status_id = :status
/* END status */
ORDER BY c.update_date DESC
LIMIT :limit OFFSET :offset
