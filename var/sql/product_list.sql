SELECT
    p.id,
    p.name,
    p.description_list,
    p.description_detail,
    p.search_word,
    p.note,
    p.create_date,
    p.update_date,
    ps.id AS status_id,
    ps.name AS status_name,
    MIN(pc.price02) AS min_price,
    MAX(pc.price02) AS max_price,
    (SELECT pi.file_name FROM product_image pi WHERE pi.product_id = p.id ORDER BY pi.sort_no LIMIT 1) AS main_image
FROM product p
LEFT JOIN mtb_product_status ps ON p.product_status_id = ps.id
LEFT JOIN product_class pc ON pc.product_id = p.id AND pc.visible = 1
/* BEGIN category_id */
INNER JOIN product_category pcat ON pcat.product_id = p.id AND pcat.category_id = :category_id
/* END category_id */
WHERE 1=1
/* BEGIN name */
AND p.name LIKE :name
/* END name */
GROUP BY p.id
ORDER BY p.update_date DESC
LIMIT :limit OFFSET :offset
