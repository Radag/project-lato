class @latoUserAutocomplete

	constructor: (@settings) ->
		@wrapper = @settings.wrapper
		@chipsData = []
		@chipsElement = $(@wrapper).find '.chips'
		@usersInput = $(@wrapper).find "input[name='users']"
		@submitButton = $(@wrapper).find "button[type='submit']"
		@init()

	init: () ->
		$(@wrapper).find(".search-user-form").on "keyup", (e) =>
			target = e.currentTarget
			if $(target).val().length > 2
				$(@wrapper).find(".loaded-user-list").hide();
				$(@wrapper).find(".users-autocomplete-loader").removeClass 'hide'
				$.nette.ajax
					url: @settings.link,
					method: 'GET',
					data:
						'term': $(target).val()
					success: (data) ->
						$(target).find(".users-autocomplete-loader").addClass 'hide'
					error: (data) ->
						#$(".users-autocomplete-loader").addClass('hide');
		
	addNewUser: (input) =>
		exist = false;
		for i, chip of @chipsData
			if chip.id is $(input).data 'user-id'
				exist = true
		if !exist
			@chipsData.push
				tag: $(input).data 'user-name'
				id: $(input).data 'user-id'                   
			@updateUsersInput()
			
		@chipsElement.chips
			data: @chipsData
			onChipDelete: (id, ele, chip) =>
				@updateUsersInput()
		$(@wrapper).find(".search-user-form").val('').focus()
				
	updateUsersInput: () ->
		toUsers = []
		for i, chip of @chipsData
			toUsers.push chip.id
		
		@usersInput.val toUsers
		@changeSubmitButton()

	changeSubmitButton: () ->
		if @chipsData.length > 0
			@chipsElement.removeClass 'hide'
			@submitButton.prop 'disabled', false
		else
			@chipsElement.addClass 'hide'
			@submitButton.prop 'disabled', true

	renew: () =>
		$(@wrapper).find(".add-user-item").on 'click', (e) =>
			target = e.currentTarget
			e.preventDefault()
			@addNewUser target
			
		$(@wrapper).find(".rest-of-all").on 'click', (e) =>
			$(@wrapper).find(".add-user-item").parent('li').removeClass 'hide'
			$(@wrapper).find(".rest-of-all").addClass 'hide'