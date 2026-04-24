--[[
  badges.lua — filtro Pandoc para cajas de advertencia (badges).

  Acepta dos sintaxis Markdown:

  1) Fenced Div (recomendada, estándar Pandoc):
       ::: {.badge-new}
       **Nuevo en v3.5.22** — Funcionalidad agregada.
       :::

  2) BlockQuote + Span (retrocompatibilidad con manual previo):
       > **Nuevo en v3.5.22** {.badge-new}
       > Funcionalidad agregada.

  En LaTeX las envuelve en entornos tcolorbox (`badgenew`, `badgewarn`,
  `badgeinfo`). En HTML/WeasyPrint las deja como Div con la clase original.

  Clases soportadas: badge-new, badge-warn, badge-info.
--]]

local badge_to_env = {
  ['badge-new']  = 'badgenew',
  ['badge-warn'] = 'badgewarn',
  ['badge-info'] = 'badgeinfo',
}

local function wrap_latex(env, blocks)
  local result = { pandoc.RawBlock('latex', '\\begin{' .. env .. '}') }
  for _, b in ipairs(blocks) do table.insert(result, b) end
  table.insert(result, pandoc.RawBlock('latex', '\\end{' .. env .. '}'))
  return result
end

-- (1) Fenced Div nativo: ::: {.badge-*}  ...  :::
function Div(el)
  if not el.classes then return nil end
  for _, c in ipairs(el.classes) do
    local env = badge_to_env[c]
    if env then
      if FORMAT:match 'latex' then
        return wrap_latex(env, el.content)
      end
      return el  -- HTML/WeasyPrint conserva la clase
    end
  end
  return nil
end

-- (2) Patrón legacy: > **X** {.badge-*}  + líneas siguientes
local function find_badge_class(inlines)
  for _, inline in ipairs(inlines) do
    if inline.t == 'Span' and inline.classes then
      for _, c in ipairs(inline.classes) do
        if badge_to_env[c] then return c, inline end
      end
    end
  end
  return nil, nil
end

function BlockQuote(el)
  if not el.content or #el.content == 0 then return nil end
  local first = el.content[1]
  if first.t ~= 'Para' and first.t ~= 'Plain' then return nil end

  local class, span = find_badge_class(first.content)
  if not class then return nil end

  -- Remover el Span marcador del primer párrafo
  local cleaned = {}
  for _, inl in ipairs(first.content) do
    if inl ~= span then table.insert(cleaned, inl) end
  end
  first.content = cleaned

  if FORMAT:match 'latex' then
    return wrap_latex(badge_to_env[class], el.content)
  end
  return pandoc.Div(el.content, pandoc.Attr('', { class }))
end
