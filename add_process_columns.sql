-- Add polo_ativo and polo_passivo columns to processes table if they don't exist
ALTER TABLE processes ADD COLUMN IF NOT EXISTS polo_ativo VARCHAR(255) AFTER tribunal;
ALTER TABLE processes ADD COLUMN IF NOT EXISTS polo_passivo VARCHAR(255) AFTER polo_ativo;