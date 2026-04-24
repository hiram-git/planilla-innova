--[[
  crossref.lua — filtro Pandoc para gestionar referencias cruzadas del manual.

  Funcionalidades:
    1. Reemplaza texto "§X.Y" por un enlace interno al heading correspondiente
       cuando exista (si no, lo deja como texto plano).
    2. Numera automáticamente figuras (figura N) y tablas (tabla N).

  Este filtro es complementario a la numeración automática de Pandoc
  (--number-sections). El foco es enriquecer la lectura sin romper el
  Markdown fuente.
--]]

-- Tabla para almacenar los headings numerados y construir el mapa §X.Y → id
local heading_map = {}

function Header(el)
  if el.identifier and el.identifier ~= '' then
    -- Pandoc asigna identifiers automáticamente; sólo registramos
    heading_map[el.identifier] = true
  end
  return nil
end

-- Contador de figuras
local fig_count = 0
function Image(el)
  if el.caption and #el.caption > 0 then
    fig_count = fig_count + 1
    table.insert(el.caption, 1, pandoc.Str(string.format('Figura %d — ', fig_count)))
  end
  return el
end

-- Reemplaza "§X.Y" en texto plano por referencias marcadas (estilo cursiva por ahora,
-- hasta que se mapeen todos los ids tras el primer pase de Pandoc).
function Str(el)
  local section_ref = el.text:match('^§(%d+%.%d+%.?%d*)$')
  if section_ref then
    return pandoc.Emph({ pandoc.Str('§' .. section_ref) })
  end
  return nil
end
