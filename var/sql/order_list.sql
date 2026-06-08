SELECT
    o.id,
    o.order_no,
    o.customer_id,
    o.name01,
    o.name02,
    o.email,
    o.phone_number,
    o.subtotal,
    o.discount,
    o.delivery_fee_total,
    o.charge,
    o.tax,
    o.total,
    o.payment_total,
    o.add_point,
    o.use_point,
    o.message,
    o.note,
    o.order_date,
    o.payment_date,
    o.create_date,
    o.update_date,
    os.id AS order_status_id,
    os.name AS order_status_name,
    pay.id AS payment_id,
    pay.method AS payment_method
FROM `order` o
LEFT JOIN mtb_order_status os ON o.order_status_id = os.id
LEFT JOIN payment pay ON o.payment_id = pay.id
WHERE 1=1
/* BEGIN customer_id */
AND o.customer_id = :customer_id
/* END customer_id */
/* BEGIN status */
AND o.order_status_id = :status
/* END status */
/* BEGIN order_no */
AND o.order_no LIKE :order_no
/* END order_no */
/* BEGIN date_from */
AND o.order_date >= :date_from
/* END date_from */
/* BEGIN date_to */
AND o.order_date <= :date_to
/* END date_to */
ORDER BY o.order_date DESC
LIMIT :limit OFFSET :offset
