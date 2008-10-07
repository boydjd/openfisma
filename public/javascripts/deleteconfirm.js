// Used to present the user an alert box asking them if they are sure they want to 
// delete the item they selected, the entryname should be defined in the form.
// If the user selects ok the function returns true, if the user selects cancel the 
// function returns false
function delok(entryname)
{
    var str = "Are you sure that you want to delete this " + entryname + "?";
    if(confirm(str) == true){
        return true;
    }
    return false;
}
