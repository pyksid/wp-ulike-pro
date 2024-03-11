/*! wp-ulike-pro - v1.8.4
 *  https://wpulike.com
 *  TechnoWich 2024;
 */


/* ================== admin/assets/js/src/scripts.js =================== */


/**
 * wp ulike admin statistics
 */
(function ($) {
  // on document ready
  $(function () {});

  $(".wp-ulike-pro-ajax-button-field").on("click", function (e) {
    e.preventDefault();
    if (
      confirm("Are you sure you want to make this change in your database?")
    ) {
      var $self = $(this),
        $loaderElement = $self.closest(".wp-ulike-pro-ajax-button");

      $loaderElement.addClass("wp-ulike-is-loading");
      $.ajax({
        data: {
          action: "wp_ulike_ajax_button_field",
          nonce: $self.data("nonce"),
          type: $self.data("type"),
          method: $self.data("action"),
        },
        dataType: "json",
        type: "POST",
        timeout: 10000,
        url: UlikeProAdminCommonConfig.AjaxUrl,
        success: function (response) {
          $loaderElement.removeClass("wp-ulike-is-loading");
          $self.addClass("wp-ulike-success-primary");
          $self.prop("value", response.data.message);
        },
      });
    }
  });

  $("#wp-ulike-pro-generate-api-key").on("click", function (e) {
    e.preventDefault();

    $(".wp-ulike-pro-api-keys").addClass("wp-ulike-is-loading");
    $.ajax({
      data: {
        action: "wp_ulike_generate_api_key",
        nonce: $(this).siblings("#wp-ulike-pro-api-keys-nonce-field").val(),
      },
      dataType: "json",
      type: "POST",
      url: UlikeProAdminCommonConfig.AjaxUrl,
      success: function (response) {
        $(".wp-ulike-pro-api-keys").removeClass("wp-ulike-is-loading");
        var $noticeElement = $("#wp-ulike-pro-api-keys-info-message");
        var noticeClassname = response.data.success ? "success" : "danger";
        // Update content
        $noticeElement
          .html(response.data.message)
          .removeClass()
          .addClass("ulf-submessage ulf-submessage-" + noticeClassname);

        if (typeof response.data.content !== "undefined") {
          $(".wp-ulike-pro-api-keys table tbody").html(response.data.content);
        }
      },
    });
  });
})(jQuery);


/* ================== admin/assets/js/src/stats.js =================== */


/**
 * wp ulike admin statistics
 */
(function ($) {
  $.fn.WpUlikeAjaxStats = function (
    dateRange,
    dataset,
    status,
    refresh,
    filter
  ) {
    // local var
    var theResponse = null;
    // jQuery ajax
    $.ajax({
      type: "POST",
      dataType: "json",
      url: UlikeProAdminCommonConfig.AjaxUrl,
      async: false,
      data: {
        action: "wp_ulike_pro_ajax_stats",
        nonce: wp_ulike_admin.nonce_field,
        dataset: dataset,
        status: status,
        filter: filter,
        refresh: refresh,
        range: JSON.stringify(dateRange),
      },
      success: function (response) {
        if (response.success) {
          theResponse = JSON.parse(response.data);
        } else {
          theResponse = null;
        }
      },
    });
    // Return the response text
    return theResponse;
  };

  // Charts stack array to save data
  window.wpUlikechartsInfo = [];

  // Charts stack array to save data
  window.wpUlikechartsElement = {};

  if (document.getElementById("wp-ulike-logs-app")) {
    new Vue({
      el: "#wp-ulike-logs-app",
      props: [],
      data() {
        return {
          isLoading: false,
          columns: [],
          rows: [],
          totalRecords: 0,
          timeout: null,
          serverParams: {
            action: "wp_ulike_pro_ajax_logs",
            nonce: wp_ulike_admin.nonce_field,
            table: "",
            selectAction: "",
            selectedItems: [],
            searchQuery: null,
            sort: {
              field: "id",
              type: "DESC",
            },
            page: 1,
            perPage: 15,
          },
        };
      },
      methods: {
        updateParams(newProps) {
          this.serverParams = Object.assign({}, this.serverParams, newProps);
        },

        onPageChange(params) {
          this.updateParams({
            page: params.currentPage,
          });
          this.loadItems();
        },

        onPerPageChange(params) {
          this.updateParams({
            perPage: params.currentPerPage,
            page: 1,
          });
          this.loadItems();
        },

        selectionChanged(params) {
          $(this.$el)
            .find("#wp-ulike-remove-logs")
            .on(
              "click",
              function (e) {
                e.preventDefault();
                var removeAlert = confirm(wp_ulike_admin.logs_notif);
                this.updateParams({
                  selectAction: "delete",
                  selectedItems: params.selectedRows,
                });
                if (removeAlert === true) {
                  this.loadItems();
                }
              }.bind(this)
            );

          if (params.selectedRows.length > 0) {
            $(this.$el).find("#wp-ulike-remove-logs").fadeIn();
          } else {
            $(this.$el).find("#wp-ulike-remove-logs").fadeOut();
          }
        },

        onSortChange(params) {
          this.updateParams({
            sort: {
              type: params[0].type,
              field: params[0].field,
            },
            page: 1,
          });
          this.loadItems();
        },

        onSearch(params) {
          this.updateParams({
            searchQuery: params.searchTerm,
            page: 1,
          });
          var self = this,
            immediate = false;
          var later = function () {
            self.timeout = null;
            if (!immediate) {
              self.loadItems();
            }
          };
          var callNow = immediate && !self.timeout;
          clearTimeout(self.timeout);
          self.timeout = setTimeout(later, 1000 || 200);
          if (callNow) {
            self.loadItems();
          }
        },

        // load items is what brings back the rows from server
        loadItems() {
          var self = this;
          // Update table name
          this.updateParams({ table: this.$el.dataset.tableName });
          // Send AJAX request
          $.ajax({
            url: UlikeProAdminCommonConfig.AjaxUrl,
            method: "POST",
            data: self.serverParams,
          }).done(function (response) {
            if (response.success) {
              response.data = JSON.parse(response.data);
              self.totalRecords = response.data.totalRecords;
              self.rows = response.data.rows;
            }
          });
        },
      },
      mounted() {
        this.loadItems();
      },
    });
  }

  if (document.getElementById("wp-ulike-stats-app")) {
    // Get all tables data
    window.wpUlikeAjaxDataset = $.fn.WpUlikeAjaxStats(
      null,
      null,
      null,
      false,
      null
    );

    if (window.wpUlikeAjaxDataset === null) {
      return;
    }

    // Get single var component
    Vue.component("get-var", {
      props: ["dataset"],
      data: function () {
        return {
          output: "...",
        };
      },
      mounted() {
        this.output = this.fetchData();
        // Remove spinner class
        this.$nextTick(function () {
          this.removeClass(this.$el.offsetParent);
        });
      },
      methods: {
        fetchData() {
          return window.wpUlikeAjaxDataset[this.dataset];
        },
        removeClass(element) {
          element.classList.remove("wp-ulike-is-loading");
        },
      },
    });
    // Get charts object component
    Vue.component("get-chart", {
      props: ["dataset", "identify", "type"],
      mounted() {
        if (this.type == "line") {
          this.planetChartData = this.fetchData();
          this.createLineChart(this.planetChartData);
        } else {
          this.createPieChart();
        }
        // Remove spinner class
        this.$nextTick(function () {
          this.removeClass(this.$el.offsetParent);
        });
      },
      methods: {
        fetchData() {
          return window.wpUlikeAjaxDataset[this.dataset];
        },
        createLineChart(chartData) {
          // Push data stats in dataset options
          // chartData.options["data"] = chartData.data;
          // And finally draw it
          this.drawChart({
            // The type of chart we want to create
            type: "line",
            // The data for our dataset
            data: {
              labels: chartData.label,
              datasets: chartData.datasets,
            },
            options: chartData.options,
          });
          // Set info for this canvas
          this.setInfo(chartData);
        },
        createPieChart() {
          // Define stack variables
          var pieData = [],
            pieBackground = [],
            pieLabels = [];
          // Get the info of each chart
          window.wpUlikechartsInfo.forEach(function (value, key) {
            pieData.push(value.sum);
            pieBackground.push(value.background);
            pieLabels.push(value.label);
          });
          // And finally draw it
          this.drawChart({
            // The type of chart we want to create
            type: "pie",
            // The data for our dataset
            data: {
              datasets: [
                {
                  data: pieData,
                  backgroundColor: pieBackground,
                },
              ],
              // These labels appear in the legend and in the tooltips when hovering different arcs
              labels: pieLabels,
            },
          });
        },
        drawChart(chartArgs) {
          // Get canvas element
          const ctx = document.getElementById(this.identify);
          // Draw Chart
          wpUlikechartsElement[this.identify] = new Chart(ctx, chartArgs);
        },
        setInfo(chartData) {
          var sumStack = 0;
          // Get the sum of total likes
          chartData.datasets[0].data.forEach(function (num) {
            sumStack += parseFloat(num) || 0;
          });
          // Upgrade wpUlikechartsInfo array
          window.wpUlikechartsInfo.push({
            type: this.identify,
            sum: sumStack,
            label: this.identify.split("wp-ulike-").pop().split("-chart")[0],
            background:
              "#" +
              (0x1000000 + Math.random() * 0xffffff).toString(16).substr(1, 6),
          });
        },
        removeClass(element) {
          element.classList.remove("wp-ulike-is-loading");
        },
      },
    });

    Vue.component("select-2", {
      template:
        '<select v-bind:name="name" class="form-control" v-bind:multiple="multiple"></select>',
      props: {
        name: "",
        options: {
          Object,
        },
        value: null,
        multiple: {
          Boolean,
          default: false,
        },
      },
      data() {
        return {
          select2data: [],
        };
      },
      mounted() {
        this.formatOptions();
        var vm = this;
        var parent = vm.$parent.$data;
        var select = $(this.$el);
        select
          .select2({
            placeholder: "Select a Status",
            theme: "bootstrap",
            data: this.select2data,
          })
          .on("change", function () {
            var value = select.val();
            vm.$emit("input", value);

            switch (this.name) {
              case "filterPostType":
                vm.$parent.updateList(JSON.stringify(value));
                break;

              case "selectStatus":
                vm.$parent.updateChart(parent.elementID, JSON.stringify(value));
                break;
            }
          });
        select.val(this.value).trigger("change");
      },
      methods: {
        formatOptions() {
          for (var key in this.options) {
            this.select2data.push({ id: key, text: this.options[key] });
          }
        },
      },
      destroyed: function () {
        $(this.$el).off().select2("destroy");
      },
    });

    VueRangedatePicker.default.install(Vue);

    const newDateObj = new Date();

    Vue.component("chart-options", {
      name: "chart-options",
      props: ["identify", "dataset", "status", "optionType"],
      data() {
        return {
          elementID: this.identify,
          selected: "",
          selectedDate: {
            start: null,
            end: null,
          },
          presetRanges: {
            today: function () {
              const n = new Date();
              const today = new Date(
                Date.UTC(n.getFullYear(), n.getMonth(), n.getDate(), 0, 0)
              );
              return {
                label: "Today",
                active: false,
                dateRange: {
                  start: today,
                  end: today,
                },
              };
            },
            thisMonth: function () {
              const n = new Date();
              const startMonth = new Date(
                Date.UTC(n.getFullYear(), n.getMonth(), 1)
              );
              const endMonth = new Date(
                Date.UTC(n.getFullYear(), n.getMonth() + 1, 0)
              );
              return {
                label: "This Month",
                active: false,
                dateRange: {
                  start: startMonth,
                  end: endMonth,
                },
              };
            },
            lastMonth: function () {
              const n = new Date();
              const startMonth = new Date(
                Date.UTC(n.getFullYear(), n.getMonth() - 1, 1)
              );
              const endMonth = new Date(
                Date.UTC(n.getFullYear(), n.getMonth(), 0)
              );
              return {
                label: "Last Month",
                active: false,
                dateRange: {
                  start: startMonth,
                  end: endMonth,
                },
              };
            },
            last7days: function () {
              const n = new Date();
              const start = new Date(
                Date.UTC(n.getFullYear(), n.getMonth(), n.getDate() - 6)
              );
              const end = new Date(
                Date.UTC(n.getFullYear(), n.getMonth(), n.getDate())
              );
              return {
                label: "Last 7 Days",
                active: false,
                dateRange: {
                  start: start,
                  end: end,
                },
              };
            },
            last30days: function () {
              const n = new Date();
              const start = new Date(
                Date.UTC(n.getFullYear(), n.getMonth(), n.getDate() - 30)
              );
              const end = new Date(
                Date.UTC(n.getFullYear(), n.getMonth(), n.getDate())
              );
              return {
                label: "Last 30 Days",
                active: false,
                dateRange: {
                  start: start,
                  end: end,
                },
              };
            },
            total: function () {
              return {
                label: "All Times",
                active: false,
                dateRange: {
                  start: "",
                  end: "",
                },
              };
            },
          },
        };
      },
      methods: {
        onDateSelected: function (daterange) {
          this.selectedDate = {
            start: daterange.start
              ? daterange.start.toISOString().split("T")[0]
              : null,
            end: daterange.end
              ? daterange.end.toISOString().split("T")[0]
              : null,
          };

          // Get status value from select-2 component
          var selectedStatus = null;
          if (typeof this.$refs["chart-options"] !== "undefined") {
            selectedStatus = JSON.stringify(
              this.$refs["chart-options"].$options.propsData.value
            );
          }

          if (this.optionType === "list") {
            this.updateList(selectedStatus);
          } else {
            this.updateChart(this.identify, selectedStatus);
          }
        },
        updateList: function (filter) {
          var listEl =
            this.$el.parentElement.nextElementSibling.firstElementChild
              .firstElementChild.firstElementChild;
          listEl.classList.add("wp-ulike-is-loading");
          var respond = $.fn.WpUlikeAjaxStats(
            this.selectedDate,
            this.dataset,
            null,
            true,
            filter
          );
          listEl.classList.remove("wp-ulike-is-loading");
          listEl.innerHTML = respond;
        },
        updateChart: function (chartID, status) {
          if (typeof wpUlikechartsElement[chartID] === "undefined") {
            return;
          }

          // Add progress class
          wpUlikechartsElement[chartID].canvas.parentElement.classList.add(
            "wp-ulike-is-loading"
          );
          var respond = $.fn.WpUlikeAjaxStats(
            this.selectedDate,
            this.dataset,
            status,
            true,
            null
          );
          // Remove Progress class
          wpUlikechartsElement[chartID].canvas.parentElement.classList.remove(
            "wp-ulike-is-loading"
          );

          if (typeof respond !== "undefined") {
            wpUlikechartsElement[chartID].data.labels = respond.label;
            wpUlikechartsElement[chartID].data.datasets = respond.datasets;
            wpUlikechartsElement[chartID].options.title.text =
              respond.options.title.text;
            wpUlikechartsElement[chartID].update();
          }
        },
      },
    });

    new Vue({
      el: "#wp-ulike-stats-app",
    });
  }

  $.fn.WpUlikeConvertToCSV = function (JSONData, ReportTitle, ShowLabel) {
    //If JSONData is not an object then JSON.parse will parse the JSON string in an Object
    var arrData = typeof JSONData != "object" ? JSON.parse(JSONData) : JSONData;

    var CSV = "";

    //This condition will generate the Label/Header
    if (ShowLabel) {
      var row = "";

      //This loop will extract the label from 1st index of on array
      for (var index in arrData[0]) {
        //Now convert each value to string and comma-seprated
        row += index + ",";
      }

      row = row.slice(0, -1);

      //append Label row with line break
      CSV += row + "\r\n";
    }

    //1st loop is to extract each row
    for (var i = 0; i < arrData.length; i++) {
      var row = "";

      //2nd loop will extract each column and convert it in string comma-seprated
      for (var index in arrData[i]) {
        row += '"' + arrData[i][index] + '",';
      }

      row.slice(0, row.length - 1);

      //add a line break after each row
      CSV += row + "\r\n";
    }

    if (CSV == "") {
      alert("Invalid data");
      return;
    }

    //this will remove the blank-spaces from the title and replace it with an underscore
    var fileName = ReportTitle.replace(/\s+/g, "-").toLowerCase();

    //Initialize file format you want csv or xls
    var uri = "data:text/csv;charset=utf-8," + escape(CSV);

    // Now the little tricky part.
    // you can use either>> window.open(uri);
    // but this will not work in some browsers
    // or you will not get the correct file extension

    //this trick will generate a temp <a /> tag
    var link = document.createElement("a");
    link.href = uri;

    //set the visibility hidden so it will not effect on your web-layout
    link.style = "visibility:hidden";
    link.download = fileName + ".csv";

    //this part will append the anchor tag and remove it after automatic click
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  // on document ready
  $(function () {
    $(".wp-ulike-match-height").matchHeight();

    $("#wp-ulike-export-logs").on("click", function (e) {
      e.preventDefault();
      $(".vgt-inner-wrap").addClass("wp-ulike-is-loading");
      var tableName = $(this).closest("#wp-ulike-logs-app").data("table-name");
      $.ajax({
        type: "POST",
        dataType: "json",
        url: UlikeProAdminCommonConfig.AjaxUrl,
        data: {
          action: "wp_ulike_pro_export_logs",
          nonce: wp_ulike_admin.nonce_field,
          table: tableName,
        },
      }).done(function (response) {
        $(".vgt-inner-wrap").removeClass("wp-ulike-is-loading");
        if (response.success) {
          $.fn.WpUlikeConvertToCSV(
            response.data.content,
            response.data.fileName,
            true
          );
        }
      });
    });
  });
})(jQuery);