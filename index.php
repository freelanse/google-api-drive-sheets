<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<form action="/google-api.php" method="POST" enctype="multipart/form-data" class="form__pictrue" id="vak">
    <input id="fio" type="text" name="fio" class="form-control" placeholder="Введите ваше ФИО" required>
    <select id="position-select" name="position_select" class="form-control">
        <option value="WEB-Дизайн" >WEB-Дизайн</option>
        <option value="Верстка">Верстка</option>
    </select>
    <input id="email" type="email" name="email" class="form-control" placeholder="Введите ваш email" required>
    <input id="phone" type="tel" name="phone" class="form-control" placeholder="Введите ваш телефон" required>
    <label for="rezume" class="file-upload-label">Загрузить резюме</label>
    <input id="rezume" type="file" name="rezume" required>
    <span id="file-name" class="file-name"></span> <!-- Для отображения имени файла -->
    <label for="rezume" class="file-upload-label">Загрузить анткету</label>
    <input id="anketa" type="file" name="anketa">
    <span id="file-name2" class="file-name2"></span> <!-- Для отображения имени файла -->
    <button type="submit" class="base__btn btn__orange">Отправить</button>
</form>
<script>
    $(document).ready(function() {
        // Обработчик для формы с ID vak
        $('#vak').on('submit', function(e) {
            e.preventDefault(); // Отменяем стандартную отправку формы

            let formData = new FormData(this); // Создаем объект FormData для отправки файлов
            const submitButton = $(this).find('button[type="submit"]');

            // Добавляем индикатор загрузки
            submitButton.text('Отправка...').prop('disabled', true);

            $.ajax({
                url: $(this).attr('action'),
                type: $(this).attr('method'),
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    // Успешная отправка
                    $('.modal2[data-form-success]').css('display', 'block');
                    $('#vak').trigger('reset'); // Сбрасываем форму
                    submitButton.text('Отправить').prop('disabled', false); // Возвращаем кнопку в исходное состояние
                    $('#file-name').text(''); // Очищаем имя файла
                    $('#file-name2').text(''); // Очищаем имя файла

                },
                error: function(xhr, status, error) {
                    // Обработка ошибки
                    alert("Произошла ошибка. Попробуйте снова.");
                    submitButton.text('Отправить').prop('disabled', false);
                }
            });
        });

        // Отображение имени загруженного файла
        $('#rezume').on('change', function() {
            const fileName = $(this).val().split('\\').pop(); // Получаем имя файла
            $('#file-name').text(fileName); // Выводим имя файла в элементе
        });

        $('#anketa').on('change', function() {
            const fileName2 = $(this).val().split('\\').pop(); // Получаем имя файла
            $('#file-name2').text(fileName2); // Выводим имя файла в элементе
        });
    });
</script>
