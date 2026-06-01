#!/bin/bash
docker compose exec -T database psql -U app app << 'EOF'
INSERT INTO breed_view (breed_id, view_count, updated_at)
VALUES
  ('abys', 42, NOW()),
  ('beng', 31, NOW()),
  ('birm', 27, NOW()),
  ('bomb', 19, NOW()),
  ('bsho', 15, NOW())
ON CONFLICT (breed_id) DO UPDATE
  SET view_count = EXCLUDED.view_count,
      updated_at = NOW();
EOF

echo "Donées insérées"