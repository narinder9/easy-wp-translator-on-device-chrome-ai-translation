class BlockFilterSorter {
  constructor() {
    this.tableBody = document.querySelector('.ewt-custom-data-table-table tbody');
    this.filters = document.querySelectorAll('.ewt-custom-data-table-filters .ewt-filter-tab');
    this.ewtDataTableObj = null;
    this.saveButtonEnabled = false;
    this.saveButtonText = false;
    this.saveButtonClass = false;
    this.saveButtonAction = false;
    this.saveButtonNonce = false;
    this.displayAjaxNotice=false;
    this.ajaxUrl = false;

    if (window.ewtCustomTableDataObject) {
      if (ewtCustomTableDataObject.save_button_enabled && '' !== ewtCustomTableDataObject.save_button_enabled) {
        this.saveButtonEnabled = ewtCustomTableDataObject.save_button_enabled;
      }
      if (ewtCustomTableDataObject.save_button_text && '' !== ewtCustomTableDataObject.save_button_text) {
        this.saveButtonText = ewtCustomTableDataObject.save_button_text;
      }
      if (ewtCustomTableDataObject.save_button_class && '' !== ewtCustomTableDataObject.save_button_class) {
        this.saveButtonClass = ewtCustomTableDataObject.save_button_class;
      }
      if (ewtCustomTableDataObject.save_button_handler && '' !== ewtCustomTableDataObject.save_button_handler) {
        this.saveButtonAction = ewtCustomTableDataObject.save_button_handler;
      }
      if (ewtCustomTableDataObject.save_button_nonce && '' !== ewtCustomTableDataObject.save_button_nonce) {
        this.saveButtonNonce = ewtCustomTableDataObject.save_button_nonce;
      }
      if (ewtCustomTableDataObject.admin_url && '' !== ewtCustomTableDataObject.admin_url) {
        this.ajaxUrl = ewtCustomTableDataObject.admin_url;
      }

      const inputFields = document.querySelectorAll('#ewt-custom-datatable tbody input[name="ewt_fields_status"]');
      inputFields.forEach(input => {
        input.addEventListener('change', this.updateStatusHandler.bind(this));
      });
    }

    if (this.tableBody) {
      this.ewtDataTable();

      this.filters.forEach(filter => {
        filter.addEventListener('input', this.datatableFilterHandler.bind(this));
      });
    }
  }

  ewtDataTable() {
    if (this.tableBody) {
      this.ewtDataTableObj = new DataTable('#ewt-custom-datatable', {
        pageLength: 25,
        infoCallback: function (settings, start, end, total, max) {
          return `Showing ${start} to ${end} of ${max} records`;
        }
      });

      this.ewtDataTableObj.on('draw.dt', function (e) {
        const rows = jQuery(this).find('tbody tr');

        if (rows.length.length === 0) {
          this.ewtDataTableObj.empty();
        }

        const length = e.dt.page.info().length;
        const page = e.dt.page.info().page;

        rows.each(function (index, row) {
          const emptyCell = row.querySelector('td.dt-empty');
          if (!emptyCell) {
            row.children[0].textContent = (page * length) + index + 1;
          }
        });
      });

      const tableWrp = document.getElementById('ewt-custom-datatable_wrapper');
      const selectWrapper = document.querySelector('.ewt-custom-data-table-filters');
      selectWrapper.remove();
      tableWrp.prepend(selectWrapper);

      if (this.saveButtonEnabled && '' !== this.saveButtonText && 'false' !== this.saveButtonText) {
        const saveButton = this.appendSaveButton();
        const lastRow = tableWrp.querySelector('.dt-layout-row:last-child');
        lastRow.before(saveButton);

        jQuery(`.${this.saveButtonClass}`).on('click', this.saveButtonHandler.bind(this));
      }
    }
  }

  datatableFilterHandler(e) {
    if (this.ewtDataTableObj) {
      let value = e.target.value;
      let wrapper = e.target.closest('.ewt-filter-tab');
      let column = parseInt(wrapper.dataset.column);
      let defaultValue = wrapper.dataset.default;
      value = value === defaultValue ? false : value;
      this.ewtDataTableObj.column(column).search(value ? new RegExp('^' + value, 'i') : '', false, false, false).draw();
    }
  }

  updateStatusHandler(e) {
    const table = jQuery('#ewt-custom-datatable').DataTable();

    if (!table) return; // DataTable not initialized
  
    const $tr = jQuery(e.target).closest('tr');
    if (!$tr.length) return; // no row found
  
    const dtRow = table.row($tr);
    if (!dtRow.node()) return; // row doesn’t exist in DataTable
  
    const checked = e.target.checked;
    const status = checked ? 'Supported' : 'Unsupported';
  
    // Make sure cell exists
    const cell = dtRow.cell(dtRow.index(), 3);
    if (!cell) return;
  
    // Update via DataTables API
    cell.data(status);
  }

  saveButtonHandler(e) {
    e.preventDefault();
    const saveBtns = jQuery(`.${this.saveButtonClass}`);

    if (saveBtns.hasClass('saving')) {
      return;
    }

    if (!this.saveButtonAction || '' === this.saveButtonAction || !this.saveButtonNonce || '' === this.saveButtonNonce || !this.ajaxUrl || '' === this.ajaxUrl) {
      return;
    }

    const selectedCheckbox = [];
    const tdNodes = this.ewtDataTableObj.column(4).nodes();

    if (tdNodes.length > 0) {
      Array.from(tdNodes).forEach(tdNode => {
        const checkbox = tdNode.querySelector('input[type="checkbox"]');
        if (checkbox && checkbox.checked) {
          selectedCheckbox.push(checkbox.value);
        }
      });
    }

    if (selectedCheckbox.length === 0) {
      return;
    }

    const apiSendData = {
      action: this.saveButtonAction,
      ewt_nonce: this.saveButtonNonce,
      save_custom_fields_data: JSON.stringify(selectedCheckbox)
    };

    saveBtns.addClass('saving').html('<span class="saving-text">Saving<span class="dot" style="--i:0"></span><span class="dot" style="--i:1"></span><span class="dot" style="--i:2"></span></span>', true);

    fetch(this.ajaxUrl, {
      method: 'POST',
      headers: {
        'content-type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'Accept': 'application/json',
      },
      body: new URLSearchParams(apiSendData)
    })
      .then(response => response.json())
      .then(data => {
        saveBtns.removeClass('saving').html(this.saveButtonText, true);

        if (data.success) {
          if (data.data.message) {
            this.appendMessageNotice(data.data.message, 'success');
          }
        }
      })
      .catch(error => {
        console.log(error);
        if (error.data.message) {
          this.appendMessageNotice(data.data.message, 'error');
        }
        saveBtns.removeClass('saving').html(this.saveButtonText, true);
        console.error(error);
      });
  }

  appendMessageNotice(message, type) {

    if(this.displayAjaxNotice){
      jQuery('#ewt-custom-fields-message-notice').remove();
      clearTimeout(this.displayAjaxNotice);
    }

    this.displayAjaxNotice=setTimeout(() => {
      this.displayAjaxNotice=false;
      jQuery('#ewt-custom-fields-message-notice').remove();
    }, 10000);

    let messageNotice = jQuery('<div id="ewt-custom-fields-message-notice" style="margin-bottom: 10px;"><p>' + message + '</p></div>');
    messageNotice.addClass('is-dismissible notice notice-' + type);
    jQuery('#ewt-settings-header').after(messageNotice);
  }

  appendSaveButton() {

    if (!this.saveButtonText || '' === this.saveButtonText || 'false' === this.saveButtonText || !this.saveButtonEnabled) {
      return;
    }

    const saveButton = document.createElement('button');
    saveButton.className = 'button button-primary ' + this.saveButtonClass;
    saveButton.textContent = this.saveButtonText;
    return saveButton;
  }
}

// Call the class after window load
window.addEventListener('load', () => {
  new BlockFilterSorter();
});