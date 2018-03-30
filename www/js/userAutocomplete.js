(function() {
  var __bind = function(fn, me){ return function(){ return fn.apply(me, arguments); }; };

  this.latoUserAutocomplete = (function() {
    function latoUserAutocomplete(settings) {
      this.settings = settings;
      this.renew = __bind(this.renew, this);
      this.addNewUser = __bind(this.addNewUser, this);
      this.wrapper = this.settings.wrapper;
      this.chipsData = [];
      this.chipsElement = $(this.wrapper).find('.chips');
      this.usersInput = $(this.wrapper).find("input[name='users']");
      this.submitButton = $(this.wrapper).find("button[type='submit']");
      this.init();
    }

    latoUserAutocomplete.prototype.init = function() {
      var _this = this;
      return $(this.wrapper).find(".search-user-form").on("keyup", function(e) {
        var target;
        target = e.currentTarget;
        if ($(target).val().length > 2) {
          $(_this.wrapper).find(".loaded-user-list").hide();
          $(_this.wrapper).find(".users-autocomplete-loader").removeClass('hide');
          return $.nette.ajax({
            url: _this.settings.link,
            method: 'GET',
            data: {
              'term': $(target).val()
            },
            success: function(data) {
              return $(target).find(".users-autocomplete-loader").addClass('hide');
            },
            error: function(data) {}
          });
        }
      });
    };

    latoUserAutocomplete.prototype.addNewUser = function(input) {
      var chip, exist, i, _ref,
        _this = this;
      exist = false;
      _ref = this.chipsData;
      for (i in _ref) {
        chip = _ref[i];
        if (chip.id === $(input).data('user-id')) {
          exist = true;
        }
      }
      if (!exist) {
        this.chipsData.push({
          tag: $(input).data('user-name'),
          id: $(input).data('user-id')
        });
        this.updateUsersInput();
      }
      return this.chipsElement.chips({
        data: this.chipsData,
        onChipDelete: function(id, ele, chip) {
          return _this.updateUsersInput();
        }
      });
    };

    latoUserAutocomplete.prototype.updateUsersInput = function() {
      var chip, i, toUsers, _ref;
      toUsers = [];
      _ref = this.chipsData;
      for (i in _ref) {
        chip = _ref[i];
        toUsers.push(chip.id);
      }
      this.usersInput.val(toUsers);
      return this.changeSubmitButton();
    };

    latoUserAutocomplete.prototype.changeSubmitButton = function() {
      if (this.chipsData.length > 0) {
        this.chipsElement.removeClass('hide');
        return this.submitButton.prop('disabled', false);
      } else {
        this.chipsElement.addClass('hide');
        return this.submitButton.prop('disabled', true);
      }
    };

    latoUserAutocomplete.prototype.renew = function() {
      var _this = this;
      $(this.wrapper).find(".add-user-item").on('click', function(e) {
        var target;
        target = e.currentTarget;
        e.preventDefault();
        return _this.addNewUser(target);
      });
      return $(this.wrapper).find(".rest-of-all").on('click', function(e) {
        $(_this.wrapper).find(".add-user-item").parent('li').removeClass('hide');
        return $(_this.wrapper).find(".rest-of-all").addClass('hide');
      });
    };

    return latoUserAutocomplete;

  })();

}).call(this);
