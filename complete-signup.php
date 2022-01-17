<?php 
    use \Library\Policy;
    $policy = new Policy;
?>

<p class="error"></p>

<div class="input-fields">
    <div class="input-field">
        <input type="text" name="firstname" placeholder=" " value="<?php echo $user->firstname; ?>" required>
        <span>
            Firstname
        </span>
        <ul class="error-list"></ul>
    </div>

    <div class="input-field">
        <input type="text" name="lastname" placeholder=" " value="<?php echo $user->lastname; ?>" required>
        <span>
            Lastname
        </span>
        <ul class="error-list"></ul>
    </div>
</div>
<?php
    if (intval($policy->get('usernames-enabled')) == 1) {
        ?>
            <div class="input-field">
                <input type="text" name="username" placeholder=" " value="<?php echo $user->username; ?>" <?php echo (intval($policy->get('usernames-required')) == 1 ? 'required' : ''); ?>>
                <span>
                    Username<?php echo (intval($policy->get('usernames-required')) == 1 ? '' : ' (optional)'); ?>
                </span>
                <ul class="error-list"></ul>
            </div>
        <?php
    }
?>

<div class="input-buttons">
    <button class="process-section">
        Save & Continue
    </button>
</div>