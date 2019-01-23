setTimeout(function () {
    var d = new Date();
    var month = d.getMonth() + 1;

    var currentDate = d.getFullYear() + '/' +
            (month < 10 ? '0' : '') + month + '/' +
            (d.getDate() < 10 ? '0' : '') + (d.getDate());
    console.log(currentDate);
    function getClassSUbjectList() {
        $.ajax({
            type: "GET",
            url: "https://stgptoc.fliplearn.com/v1/class?boardCode=cbse",
            success: function (data) {
                var classList = {};
                var objRes = JSON.parse(data);
                $.each(objRes.response, function (classIndex, classValue) {
                    var subjectList = {};
                    $.each(classValue.subjects, function (subjectIndex, subjectValue) {
                        subjectList[subjectIndex] = {
                            subjectName: subjectValue.subjectName,
                            subjectCode: subjectValue.subjectCode
                        }
                    });
                    classList[classValue.classId] = JSON.stringify(subjectList);
                });
                window.localStorage.setItem('classSubjectDate', currentDate);
                window.localStorage.setItem('classSubject', JSON.stringify(classList));
            },
        });
    }
    if (!window.localStorage.getItem('classSubjectDate')) {
        var classSubjectDate = window.localStorage.getItem('classSubjectDate');
        if (classSubjectDate != "") {
            getClassSUbjectList();
        } else if ((Date.parse(classSubjectDate) >= Date.parse(currentDate))) {
            getClassSUbjectList();
        }
    }

    $('document,body').on('change', '#primecontent_class', function () {
        var classSubjectList = JSON.parse(window.localStorage.getItem('classSubject'));
        var classId = $(this).val();
        var subjectList = JSON.parse(classSubjectList[classId]);
        $("#primecontent_subject").html("");
        $("#primecontent_subject").append("<option value=''>All Subject</option>");
        $.each(subjectList, function (subjectIndex, subjectValue) {
            $("#primecontent_subject").append("<option value='" + subjectValue.subjectCode + "'>" + subjectValue.subjectName + "</option>");
        });
    });


}, 1000);
