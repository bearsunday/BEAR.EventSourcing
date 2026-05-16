SELECT
    id,
    name,
    product_code AS code,
    note,
    description_list,
    description_detail,
    search_word,
    free_area,
    create_date,
    update_date,
    product_status_id AS status_id
FROM dtb_product
WHERE (:name IS NULL OR name LIKE CONCAT('%', :name, '%'))
  AND (:categoryId IS NULL OR id IN (
      SELECT product_id FROM dtb_product_category WHERE category_id = :categoryId
  ))
  AND (:statusId IS NULL OR product_status_id = :statusId)
ORDER BY update_date DESC, id DESC
