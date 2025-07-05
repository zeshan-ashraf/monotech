// help status

$(document).on("change", ".helpStatus", function () {
    let status = $(this).val();
    let id = $(this).attr("data-id");
    let url = $(this).data("url");
    console.log(status);
    console.log(id);
    $.ajax({
        type: "GET",
        data: {
            status: status,
            id: id,
        },
        url: url,
        success: function (response) {
            toastr.success("Updated Successfully");
        },
    });
});
$(document).on("click", ".permission_all", function () {
    $(".check_per").prop("checked", !$(".check_per").prop("checked"));
});

// permission search
$(document).on("keyup", ".search_permission", function (e) {
    var value = $(this).val().toLowerCase();
    searchExpansion(value);
});
// permission search function
function searchExpansion(value) {
    var ul = $(".list-group");
    //get all lis but not the one having search input
    var li = ul.find("li");
    //hide all lis
    li.hide();
    li.filter(function () {
        var text = $(this).text().toLowerCase();
        return text.indexOf(value) >= 0;
    }).show();
}

$(document).on("submit", ".submit_form", function (e) {
    e.preventDefault();
    if (!validate()) return false;
    if ($("div").hasClass("alert-dangers")) {
        return false;
    }
    // return false;
    var form = $(this);
    var submit_btn = $(form).find(".submit_btn");
    $(submit_btn).prop("disabled", true);
    $(submit_btn).closest("div").find(".loader").removeClass("d-none");
    $(".sipnner").removeClass("d-none");
    // console.log(from);
    var data = new FormData(this);
    $(form).find(".submit_btn").prop("disabled", true);
    $.ajax({
        type: "POST",
        data: data,
        cache: !1,
        contentType: !1,
        processData: !1,
        url: $(form).attr("action"),
        async: true,
        headers: {
            "cache-control": "no-cache",
        },
        success: function (response) {
            $(".sipnner").addClass("d-none");
            $(".modal").modal("hide");
            if (response.success) {
                toastr.success(response.success);
            }
            if (response.error) {
                toastr.error(response.error);
            }
            if(response.route)
            {
                window.location.href = response.route;
            }
            else{
                setTimeout(function () {
                    window.location.reload();
                }, 2000);
            }
           
        },
        error: function (xhr, status, error) {
            $(submit_btn).prop("disabled", false);
            $(submit_btn).closest("div").find(".loader").addClass("d-none");
            if (xhr.status == 422) {
                $(form).find("div.alert").remove();
                var errorObj = xhr.responseJSON.errors;
                $.map(errorObj, function (value, index) {
                    var appendIn = $(form)
                        .find('[name="' + index + '"]')
                        .closest("div");
                    if (!appendIn.length) {
                        toastr.error(value[0]);
                    } else {
                        $(appendIn).append(
                            '<div class="alert alert-danger" style="padding: 1px 5px;font-size: 12px"> ' +
                                value[0] +
                                "</div>"
                        );
                        $(".sipnner").addClass("d-none");
                    }
                });
                $(form).find(".submit_btn").prop("disabled", false);
            } else {
                $(form).find(".submit_btn").prop("disabled", false);
                toastr.error("Unknown Error!");
            }
        },
    });
});

function validate(type = null) {
    var valid = true;
    var div = "";
    $(".alert-danger").remove();
    $(".required:visible").each(function () {
        if (
            $(this).val() == "" ||
            $(this).val() === null ||
            $(this).attr("type") == "radio" ||
            ($(this).attr("type") == "checkbox" &&
                $('[name="' + $(this).attr("name") + '"]:checked').val() ==
                    undefined)
        ) {
            if (type != null) {
                div = "td";
            } else {
                $(this).attr("type") == "checkbox"
                    ? (div = ".row")
                    : (div = "div");
            }
            var name = $(this).attr("name");
            // console.log(name);
            $(this)
                .closest(div)
                .append(
                    '<div class="alert-danger" data-field=' +
                        name +
                        ">This field is required</div>"
                );
            valid = false;
        }
    });
    if (!valid) {
        var input = $(".alert-danger:first").attr("data-field");
        $('[name="' + input + '"]').focus();
    }
    return valid;
}

$(document).on("mousemove", ".h4Btn", function (e) {
    $(".h4").css("left", e.clientX + 10).css("top", e.clientY - 300);
});
