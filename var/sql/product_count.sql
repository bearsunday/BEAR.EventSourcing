SELECT COUNT(DISTINCT p.id) AS cnt
FROM product p
/* BEGIN category_id */
INNER JOIN product_category pcat ON pcat.product_id = p.id AND pcat.category_id = :category_id
/* END category_id */
WHERE 1=1
/* BEGIN name */
AND p.name LIKE :name
/* END name */
