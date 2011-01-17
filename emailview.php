<?php
include("emailManager.php");
include("includes/conn.php");

$email_query = Emailer::get_email_info_query($CDB, $_GET['email_id']);
if(mysql_num_rows($email_query))  {
  $email = mysql_fetch_array($email_query);
  foreach($email as $key => $value)  {
    $email[$key] = htmlentities($value);
  }
}

$options_query = Emailer::get_email_debug_options_query($CDB, $_GET['email_id']);

?>

<?php include("includes/page-header.php"); ?>
<a href='emailmgr.php?email_id=<?php echo $email_id; ?>'>Return to Email Manager</a>
<table>
<tbody>
  <tr>
    <th colspan='2'>Email values</th>
  </tr>
  <tr>
    <th>To</th>
    <td><?php echo $email['mail_to']; ?></td>
  </tr>
  <tr>
    <th>Subject</th>
    <td><?php echo $email['subject']; ?></td>
  </tr>
  <tr>
    <th>Message</th>
    <td><?php echo html_entity_decode($email['message']); ?></td>
  </tr>
  <tr>
    <th>Headers</th>
    <td><?php echo $email['headers']; ?></td>
  </tr>
  <tr>
    <th>Date Queued</th>
    <td><?php echo $email['date_queued']; ?></td>
  </tr>
  <tr>
    <th>Date Sent</th>
    <td><?php echo $email['date_sent']; ?></td>
  </tr>
  <tr>
    <th>Sent Status</th>
    <td><?php echo $email['sent_status']; ?></td>
  </tr>
  <tr>
    <th colspan='2'>Debug Values</th>
  </tr>
  <?php while($option = mysql_fetch_array($options_query)) : ?>
    <tr>
      <th><?php echo htmlentities($option['name']); ?></th>
      <td><?php echo htmlentities($option['value']); ?></td>
    </tr>
  <?php endwhile; ?>
</tbody>
</table>
<?php include("includes/page-footer.php"); ?>

