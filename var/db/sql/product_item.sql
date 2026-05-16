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
WHERE id = :id
LIMIT 1
