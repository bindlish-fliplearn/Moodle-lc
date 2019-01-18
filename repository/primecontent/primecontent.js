setTimeout(function(){
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
                classList[classValue.classCode] = JSON.stringify(subjectList);
            });
            window.localStorage.setItem('classSubject', JSON.stringify(classList));
        },
    });
}
if(!window.localStorage.getItem('classSubject')) {
    getClassSUbjectList();
}

$('document,body').on('change', '#primecontent_class', function() {
    var classSubjectList = JSON.parse(window.localStorage.getItem('classSubject'));
    var classCode = $(this).val();
     var subjectList = JSON.parse(classSubjectList[classCode]);
     $("#primecontent_subject").html("");
     $.each(subjectList, function (subjectIndex, subjectValue) {
        $("#primecontent_subject").append("<option value='" +subjectValue.subjectCode+ "'>" +subjectValue.subjectName+ "</option>");    
     }); 
});

    
}, 1000);
