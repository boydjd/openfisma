require 'digest/sha2'

class User < ActiveRecord::Base

  MINIMUM_PASSPHRASE_LENGTH = 8
  MAXIMUM_PASSPHRASE_LENGTH = 40
  
  # Validates the presence of a username
  validates_presence_of     :name

  # Validates the username is unique
  validates_uniqueness_of   :name
  
  # Enforces password length of 8 to 20 characters
  validates_length_of       :password, :in => MINIMUM_PASSPHRASE_LENGTH..MAXIMUM_PASSPHRASE_LENGTH

  # Password should contain at least one numeric character.
  # Password should contain at least one lower case character
  # Password should contain at least on upper case character
  # Password should contain at least on special character
  # Password can have special characters from 20 to 7E ascii values.
  validates_format_of       :password, 
                            :with => /^.*(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[@#'$%^&+=]).*$/,
                            :message => "must contain at least one number [1-9], one upper case character [A-Z], one lower case character [a-z], and one special character [@#'$%^&+=]"
  # Secondary password complexity requirement which does not require special characters
  #                          :with => /^.*(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).*$/,
  #                          :message => "must contain at least one number [1-9], one upper case character [A-Z], one lower case character [a-z]"


  # Validates the two passwords provided by the user match
  attr_accessor             :password_confirmation
  validates_confirmation_of :password
  
  # Validates that an email exists and in the correct format
  # username @ domain . qualifier (com/biz/net)
  #validates_presence_of     :email
  #validates_format_of       :email,
  #                          :with => /\A[\w\._%-]+@[\w\.-]+\.[a-zA-Z]{2,4}\z/,
  #                          :message => "must be in a valid format"
  
  def self.authenticate(name, password)
    user = self.find_by_name(name)
    if user
      expected_password = encrypted_password(password, user.salt)
      if user.hashed_password != expected_password
        user = nil
      end
    end
    user
  end

  def password
    @password
  end
    
  def password=(pwd)
    @password = pwd
    create_new_salt
    self.hashed_password = User.encrypted_password(self.password, self.salt)
  end

#  def before_destroy
#    if User.count.zero?
#      raise "Can't delete last user"
#    end
#  end     
  
  private

  # Creates a random salt to combine with the password
  def create_new_salt
    self.salt = self.object_id.to_s + rand.to_s
  end

  # Combines the random salt with the password provided by the user
  def self.encrypted_password(password, salt)
    string_to_hash = password + salt
    Digest::SHA256.hexdigest(string_to_hash)
  end
  
end