class @KXDrawRender

  EL_ATTR_TEMPLATE_ID = "data-kx-draw-name"
  EL_ATTR_UNIQUE_ID = "data-kx-draw-id"

  OBJ_VAR_TEMPLATE_ID = "_kx_draw_template_name"
  OBJ_VAR_UNIQUE_ID = "_kx_draw_unique_id"

  @templates = null
  @data = null
  @partials = null

  constructor: (options) ->

    {@templates, @data, @partials} = options

    for name, raw of @partials
      Handlebars.registerPartial(name, raw)

    return

  getRawTemplates: -> $.extend {}, @templates
  getRawData: -> $.extend {}, @data
  getRawPartials: -> $.extend {}, @partials

  # -----------

  getDataByUniqId: (templateName, id) ->

    bit = @getRawData()?[templateName]?[id]
    unless bit?
      console.error("Can't get data from template '#{templateName}' with id #{id}. Not found!")
      return false

    return bit

  getNodeByUniqId: (templateName, id) ->

    $rootNode = $ "[#{EL_ATTR_TEMPLATE_ID}='#{templateName}'][#{EL_ATTR_UNIQUE_ID}='#{id}']"
    unless ($rootNode? && $rootNode.length)
      console.error "Node #{templateName}/#{id} not found."
      return false

    return $rootNode

  render: (templateName, id) ->

    template = @getRawTemplates()?[templateName]
    unless template?
      console.error "Can't render #{templateName}. Template with this name - not found."
      return

    context = @getDataByUniqId templateName, id
    return unless context

    $rootNode = @getNodeByUniqId templateName, id
    return unless $rootNode

    # Render template
    renderer = Handlebars.compile template
    doc = $(renderer context)

    $rootNode.html doc.html()
    return doc

  update: (templateName, id, newData, assign = true) ->

    context = @getDataByUniqId templateName, id
    newContext = {}

    if (assign)
      newContext = $.extend {}, context, newData
    else
      newContext = newData
      newContext[OBJ_VAR_TEMPLATE_ID] = context[OBJ_VAR_TEMPLATE_ID]
      newContext[OBJ_VAR_UNIQUE_ID] = context[OBJ_VAR_UNIQUE_ID]

    @data[templateName][id] = newContext
    return @render templateName, id

